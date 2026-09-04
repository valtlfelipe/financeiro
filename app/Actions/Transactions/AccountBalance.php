<?php

namespace App\Actions\Transactions;

use App\Models\Account;
use App\Models\Transaction;
use App\Support\MinorAmount;
use App\TransactionType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class AccountBalance
{
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
}
