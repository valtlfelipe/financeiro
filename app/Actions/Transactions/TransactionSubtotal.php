<?php

namespace App\Actions\Transactions;

use App\Models\Transaction;
use App\Support\MinorAmount;
use App\TransactionType;
use Illuminate\Database\Eloquent\Builder;

class TransactionSubtotal
{
    /**
     * @param  Builder<Transaction>  $query
     * @return array{count: int, income_minor: string, expense_minor: string, transfer_minor: string}
     */
    public function handle(Builder $query): array
    {
        $totals = (clone $query)
            ->toBase()
            ->selectRaw('COUNT(*) AS transaction_count')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN type = ? THEN amount_minor ELSE 0 END), 0) AS income_minor',
                [TransactionType::Income->value],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN type = ? THEN amount_minor ELSE 0 END), 0) AS expense_minor',
                [TransactionType::Expense->value],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN type = ? THEN amount_minor ELSE 0 END), 0) AS transfer_minor',
                [TransactionType::Transfer->value],
            )
            ->first();

        return [
            'count' => (int) $totals->transaction_count,
            'income_minor' => $this->normalize($totals->income_minor),
            'expense_minor' => $this->normalize($totals->expense_minor),
            'transfer_minor' => $this->normalize($totals->transfer_minor),
        ];
    }

    private function normalize(mixed $value): string
    {
        return is_int($value) || is_string($value) ? MinorAmount::normalize($value) : '0';
    }
}
