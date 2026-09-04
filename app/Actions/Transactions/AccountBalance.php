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
    /** @return Collection<int, Account> */
    public function currentAccounts(Workspace $workspace): Collection
    {
        $today = $workspace->today();
        $accounts = $this->balanceRows($workspace, [
            'current' => ['date' => $today, 'settled_only' => true],
        ], includeArchived: false);

        return $accounts->each(function (Account $account) use ($today): void {
            $balance = $today->isBefore($account->balance_date)
                ? '0'
                : MinorAmount::add($account->initial_balance_minor, $this->rowDelta($account, 'current'));
            $account->setAttribute('balance_minor', $balance);
        });
    }

    /**
     * @return array{opening: string, forecast: string, realized: string}
     */
    public function monthlyPositions(
        Workspace $workspace,
        CarbonImmutable $openingDate,
        CarbonImmutable $forecastDate,
        CarbonImmutable $realizedDate,
    ): array {
        $today = $workspace->today();
        $positions = [
            'opening' => ['date' => $openingDate, 'settled_only' => $openingDate->isBefore($today)],
            'forecast' => ['date' => $forecastDate, 'settled_only' => $forecastDate->isBefore($today)],
            'realized' => ['date' => $realizedDate, 'settled_only' => true],
        ];
        $accounts = $this->balanceRows($workspace, $positions, includeArchived: true);
        $totals = ['opening' => '0', 'forecast' => '0', 'realized' => '0'];

        foreach ($accounts as $account) {
            foreach ($positions as $name => $position) {
                $accountBalance = $position['date']->isBefore($account->balance_date)
                    ? '0'
                    : MinorAmount::add($account->initial_balance_minor, $this->rowDelta($account, $name));
                $totals[$name] = MinorAmount::add($totals[$name], $accountBalance);
            }
        }

        return $totals;
    }

    public function handle(Account $account): string
    {
        if ($account->workspace->today()->isBefore($account->balance_date)) {
            return '0';
        }

        return $this->sum(
            $this->movements($account)
                ->whereNotNull('settled_at')
                ->whereDate('due_on', '>=', $account->balance_date->toDateString())
                ->whereDate('due_on', '<=', $account->workspace->today()->toDateString()),
            $account,
        );
    }

    public function settledThrough(Account $account, CarbonImmutable $date): string
    {
        if ($date->isBefore($account->balance_date)) {
            return '0';
        }

        return $this->sum(
            $this->movements($account)
                ->whereNotNull('settled_at')
                ->whereDate('due_on', '>=', $account->balance_date->toDateString())
                ->whereDate('due_on', '<=', $date->toDateString()),
            $account,
        );
    }

    public function projectedThrough(Account $account, CarbonImmutable $date): string
    {
        if ($date->isBefore($account->balance_date)) {
            return '0';
        }

        $today = $account->workspace->today();

        if ($today->isBefore($account->balance_date)) {
            return $this->sum(
                $this->movements($account)
                    ->whereDate('due_on', '>=', $account->balance_date->toDateString())
                    ->whereDate('due_on', '<=', $date->toDateString()),
                $account,
            );
        }

        if ($date->isBefore($today)) {
            return $this->settledThrough($account, $date);
        }

        $projectionDelta = $this->delta(
            $this->movements($account)
                ->whereDate('due_on', '>=', $account->balance_date->toDateString())
                ->whereDate('due_on', '<=', $date->toDateString())
                ->where(fn (Builder $query) => $query
                    ->whereNull('settled_at')
                    ->orWhereDate('due_on', '>', $today->toDateString())),
            $account,
        );

        return MinorAmount::add($this->handle($account), $projectionDelta);
    }

    /** @return Builder<Transaction> */
    private function movements(Account $account): Builder
    {
        return Transaction::query()
            ->where('workspace_id', $account->workspace_id)
            ->where(fn (Builder $query) => $query
                ->where('account_id', $account->id)
                ->orWhere('destination_account_id', $account->id));
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
     * @param  array<string, array{date: CarbonImmutable, settled_only: bool}>  $positions
     * @return Collection<int, Account>
     */
    private function balanceRows(Workspace $workspace, array $positions, bool $includeArchived): Collection
    {
        $query = Account::query()
            ->where('accounts.workspace_id', $workspace->id)
            ->when(! $includeArchived, fn (Builder $accountQuery) => $accountQuery->where('accounts.is_archived', false))
            ->leftJoinSub($this->movementLedger($workspace->id), 'movements', fn ($join) => $join
                ->on('movements.account_id', '=', 'accounts.id'))
            ->select([
                'accounts.id',
                'accounts.workspace_id',
                'accounts.name',
                'accounts.type',
                'accounts.initial_balance_minor',
                'accounts.balance_date',
                'accounts.icon',
                'accounts.color',
                'accounts.is_archived',
            ]);

        foreach ($positions as $name => $position) {
            $sql = match ($name.'.'.($position['settled_only'] ? 'settled' : 'all')) {
                'current.settled' => 'COALESCE(SUM(CASE WHEN DATE(movements.due_on) >= DATE(accounts.balance_date) AND DATE(movements.due_on) <= ? AND movements.settled_at IS NOT NULL THEN movements.delta_minor ELSE 0 END), 0) AS current_delta',
                'opening.settled' => 'COALESCE(SUM(CASE WHEN DATE(movements.due_on) >= DATE(accounts.balance_date) AND DATE(movements.due_on) <= ? AND movements.settled_at IS NOT NULL THEN movements.delta_minor ELSE 0 END), 0) AS opening_delta',
                'opening.all' => 'COALESCE(SUM(CASE WHEN DATE(movements.due_on) >= DATE(accounts.balance_date) AND DATE(movements.due_on) <= ? THEN movements.delta_minor ELSE 0 END), 0) AS opening_delta',
                'forecast.settled' => 'COALESCE(SUM(CASE WHEN DATE(movements.due_on) >= DATE(accounts.balance_date) AND DATE(movements.due_on) <= ? AND movements.settled_at IS NOT NULL THEN movements.delta_minor ELSE 0 END), 0) AS forecast_delta',
                'forecast.all' => 'COALESCE(SUM(CASE WHEN DATE(movements.due_on) >= DATE(accounts.balance_date) AND DATE(movements.due_on) <= ? THEN movements.delta_minor ELSE 0 END), 0) AS forecast_delta',
                'realized.settled' => 'COALESCE(SUM(CASE WHEN DATE(movements.due_on) >= DATE(accounts.balance_date) AND DATE(movements.due_on) <= ? AND movements.settled_at IS NOT NULL THEN movements.delta_minor ELSE 0 END), 0) AS realized_delta',
                default => throw new \InvalidArgumentException("Unsupported balance position: {$name}"),
            };
            $query->selectRaw($sql, [$position['date']->toDateString()]);
        }

        return $query
            ->groupBy([
                'accounts.id',
                'accounts.workspace_id',
                'accounts.name',
                'accounts.type',
                'accounts.initial_balance_minor',
                'accounts.balance_date',
                'accounts.icon',
                'accounts.color',
                'accounts.is_archived',
            ])
            ->orderBy('accounts.id')
            ->get();
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
}
