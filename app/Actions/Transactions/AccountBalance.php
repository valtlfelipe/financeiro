<?php

namespace App\Actions\Transactions;

use App\Models\Account;
use App\Models\Transaction;
use App\TransactionType;
use Illuminate\Database\Eloquent\Builder;

class AccountBalance
{
    public function handle(Account $account): int
    {
        $result = Transaction::query()
            ->where('workspace_id', $account->workspace_id)
            ->whereNotNull('settled_at')
            ->whereDate('due_on', '>', $account->balance_date->toDateString())
            ->where(fn (Builder $query) => $query
                ->where('account_id', $account->id)
                ->orWhere('destination_account_id', $account->id))
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

        return $account->initial_balance_minor + (is_numeric($delta) ? (int) $delta : 0);
    }
}
