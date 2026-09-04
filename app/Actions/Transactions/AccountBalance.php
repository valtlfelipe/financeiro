<?php

namespace App\Actions\Transactions;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\Workspace;
use App\Support\MinorAmount;
use App\TransactionType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AccountBalance
{
    /**
     * Movements recorded before a dated opening balance are already baked into it.
     */
    private const OPENING_BALANCE_GUARD = '(accounts.initial_balance_minor = 0 OR DATE(movements.due_on) >= DATE(accounts.balance_date))';

    /**
     * Settled movements reach the account on their due date, or on the day they were
     * settled when that happened first. Takes the target date and its settlement cutoff.
     */
    private const REALIZED_CONDITION = '(movements.settled_at IS NOT NULL AND (DATE(movements.due_on) <= ? OR movements.settled_at < ?))';

    /**
     * Today's realized position plus every movement dated through the target date.
     * Each ledger row is summed once, so an entry already realized is never added twice.
     * Takes today, today's settlement cutoff, and the target date.
     */
    private const PROJECTED_CONDITION = '('.self::REALIZED_CONDITION.' OR DATE(movements.due_on) <= ?)';

    /** @return Collection<int, Account> */
    public function currentAccounts(Workspace $workspace): Collection
    {
        $today = $workspace->today();
        $accounts = $this->balanceRows($workspace, [
            'current' => ['date' => $today, 'projected' => false],
        ], includeArchived: false);

        return $accounts->each(function (Account $account) use ($today): void {
            $balance = $this->isBeforeOpeningBalance($account, $today)
                ? '0'
                : MinorAmount::add($account->initial_balance_minor, $this->rowDelta($account, 'current'));
            $account->setAttribute('balance_minor', $balance);
        });
    }

    /**
     * @return array{
     *     opening: string,
     *     forecast: string,
     *     realized: string,
     *     accounts: list<array{
     *         id: int,
     *         name: string,
     *         color: string,
     *         is_archived: bool,
     *         realized_balance_minor: string,
     *         forecast_balance_minor: string
     *     }>
     * }
     */
    public function monthlyPositions(
        Workspace $workspace,
        CarbonImmutable $openingDate,
        CarbonImmutable $forecastDate,
        CarbonImmutable $realizedDate,
    ): array {
        $today = $workspace->today();
        $positions = [
            'opening' => ['date' => $openingDate, 'projected' => ! $openingDate->isBefore($today)],
            'forecast' => ['date' => $forecastDate, 'projected' => ! $forecastDate->isBefore($today)],
            'realized' => ['date' => $realizedDate, 'projected' => false],
        ];
        $accounts = $this->balanceRows($workspace, $positions, includeArchived: true);
        $totals = ['opening' => '0', 'forecast' => '0', 'realized' => '0'];
        $accountPositions = [];

        foreach ($accounts as $account) {
            $balances = [];

            foreach ($positions as $name => $position) {
                $accountBalance = $this->isBeforeOpeningBalance($account, $position['date'])
                    ? '0'
                    : MinorAmount::add($account->initial_balance_minor, $this->rowDelta($account, $name));
                $totals[$name] = MinorAmount::add($totals[$name], $accountBalance);
                $balances[$name] = $accountBalance;
            }

            if (! $account->is_archived || $balances['realized'] !== '0' || $balances['forecast'] !== '0') {
                $accountPositions[] = [
                    'id' => $account->id,
                    'name' => $account->name,
                    'color' => $account->color,
                    'is_archived' => $account->is_archived,
                    'realized_balance_minor' => $balances['realized'],
                    'forecast_balance_minor' => $balances['forecast'],
                ];
            }
        }

        return [...$totals, 'accounts' => $accountPositions];
    }

    public function handle(Account $account): string
    {
        return $this->settledThrough($account, $account->workspace->today());
    }

    public function settledThrough(Account $account, CarbonImmutable $date): string
    {
        if ($this->isBeforeOpeningBalance($account, $date)) {
            return '0';
        }

        return $this->sum($this->realizedMovements($account, $date), $account);
    }

    public function projectedThrough(Account $account, CarbonImmutable $date): string
    {
        if ($this->isBeforeOpeningBalance($account, $date)) {
            return '0';
        }

        $today = $account->workspace->today();

        if ($date->isBefore($today)) {
            return $this->settledThrough($account, $date);
        }

        return $this->sum(
            $this->movements($account)->where(fn (Builder $query) => $query
                ->where(fn (Builder $realized) => $this->applyRealized($realized, $account, $today))
                ->orWhereDate('due_on', '<=', $date->toDateString())),
            $account,
        );
    }

    /** @return Builder<Transaction> */
    private function movements(Account $account): Builder
    {
        return Transaction::query()
            ->where('workspace_id', $account->workspace_id)
            ->where(fn (Builder $query) => $query
                ->where('account_id', $account->id)
                ->orWhere('destination_account_id', $account->id))
            ->when($this->hasDatedOpeningBalance($account), fn (Builder $query) => $query
                ->whereDate('due_on', '>=', $account->balance_date->toDateString()));
    }

    /** @return Builder<Transaction> */
    private function realizedMovements(Account $account, CarbonImmutable $date): Builder
    {
        return $this->applyRealized($this->movements($account), $account, $date);
    }

    /**
     * A settled movement reaches the account on its due date, or on the day it was
     * settled when that happened first.
     *
     * @param  Builder<Transaction>  $query
     * @return Builder<Transaction>
     */
    private function applyRealized(Builder $query, Account $account, CarbonImmutable $date): Builder
    {
        return $query
            ->whereNotNull('settled_at')
            ->where(fn (Builder $realized) => $realized
                ->whereDate('due_on', '<=', $date->toDateString())
                ->orWhere('settled_at', '<', $this->settlementCutoff($account->workspace, $date)));
    }

    /** @param Builder<Transaction> $query */
    private function sum(Builder $query, Account $account): string
    {
        return MinorAmount::add($account->initial_balance_minor, $this->delta($query, $account));
    }

    /** @param Builder<Transaction> $query */
    private function delta(Builder $query, Account $account): string
    {
        $result = $query
            ->toBase()
            ->selectRaw(
                <<<'SQL'
                    COALESCE(SUM(CASE
                        WHEN type = ? AND account_id = ? THEN amount_minor
                        WHEN type = ? AND account_id = ? THEN -amount_minor
                        WHEN type = ? THEN
                            (CASE WHEN destination_account_id = ? THEN amount_minor ELSE 0 END)
                            - (CASE WHEN account_id = ? THEN amount_minor ELSE 0 END)
                        ELSE 0
                    END), 0) AS balance_delta
                    SQL,
                [
                    TransactionType::Income->value,
                    $account->id,
                    TransactionType::Expense->value,
                    $account->id,
                    TransactionType::Transfer->value,
                    $account->id,
                    $account->id,
                ],
            )
            ->first();
        $delta = $result?->balance_delta;

        return is_int($delta) || is_string($delta) ? MinorAmount::normalize($delta) : '0';
    }

    /**
     * @param  array<string, array{date: CarbonImmutable, projected: bool}>  $positions
     * @return Collection<int, Account>
     */
    private function balanceRows(Workspace $workspace, array $positions, bool $includeArchived): Collection
    {
        $today = $workspace->today();
        $columns = [
            'accounts.id',
            'accounts.workspace_id',
            'accounts.name',
            'accounts.type',
            'accounts.initial_balance_minor',
            'accounts.balance_date',
            'accounts.icon',
            'accounts.color',
            'accounts.is_archived',
        ];
        $query = Account::query()
            ->where('accounts.workspace_id', $workspace->id)
            ->when(! $includeArchived, fn (Builder $accountQuery) => $accountQuery->where('accounts.is_archived', false))
            ->leftJoinSub($this->movementLedger($workspace->id), 'movements', fn ($join) => $join
                ->on('movements.account_id', '=', 'accounts.id'))
            ->select($columns);

        foreach ($positions as $name => $position) {
            $alias = match ($name) {
                'current' => 'current_delta',
                'opening' => 'opening_delta',
                'forecast' => 'forecast_delta',
                'realized' => 'realized_delta',
                default => throw new \InvalidArgumentException("Unsupported balance position: {$name}"),
            };
            $condition = $position['projected'] ? self::PROJECTED_CONDITION : self::REALIZED_CONDITION;
            $bindings = $position['projected']
                ? [...$this->realizedBindings($workspace, $today), $position['date']->toDateString()]
                : $this->realizedBindings($workspace, $position['date']);

            $query->selectRaw(
                'COALESCE(SUM(CASE WHEN '.self::OPENING_BALANCE_GUARD.' AND '.$condition
                    .' THEN movements.delta_minor ELSE 0 END), 0) AS '.$alias,
                $bindings,
            );
        }

        return $query
            ->groupBy($columns)
            ->orderBy('accounts.id')
            ->get();
    }

    /** @return list<string> */
    private function realizedBindings(Workspace $workspace, CarbonImmutable $date): array
    {
        return [$date->toDateString(), $this->settlementCutoff($workspace, $date)];
    }

    /**
     * The first instant that no longer belongs to the given civil date in the workspace.
     */
    private function settlementCutoff(Workspace $workspace, CarbonImmutable $date): string
    {
        return CarbonImmutable::parse($date->toDateString(), $workspace->timezone)
            ->addDay()
            ->startOfDay()
            ->utc()
            ->format('Y-m-d H:i:s');
    }

    private function movementLedger(int $workspaceId): QueryBuilder
    {
        $sourceMovements = DB::table('transactions')
            ->select(['account_id', 'due_on', 'settled_at'])
            ->selectRaw(
                'CASE WHEN type = ? THEN amount_minor ELSE -amount_minor END AS delta_minor',
                [TransactionType::Income->value],
            )
            ->where('workspace_id', $workspaceId)
            ->whereNull('deleted_at');
        $destinationMovements = DB::table('transactions')
            ->selectRaw('destination_account_id AS account_id, due_on, settled_at, amount_minor AS delta_minor')
            ->where('workspace_id', $workspaceId)
            ->where('type', TransactionType::Transfer->value)
            ->whereNull('deleted_at');

        return $sourceMovements->unionAll($destinationMovements);
    }

    private function rowDelta(Account $account, string $position): string
    {
        $delta = $account->getAttribute($position.'_delta');

        return is_int($delta) || is_string($delta) ? MinorAmount::normalize($delta) : '0';
    }

    private function hasDatedOpeningBalance(Account $account): bool
    {
        return $account->initial_balance_minor !== 0;
    }

    private function isBeforeOpeningBalance(Account $account, CarbonImmutable $date): bool
    {
        return $this->hasDatedOpeningBalance($account) && $date->isBefore($account->balance_date);
    }
}
