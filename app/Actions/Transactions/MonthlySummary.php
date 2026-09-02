<?php

namespace App\Actions\Transactions;

use App\Models\Transaction;
use App\Models\Workspace;
use App\TransactionType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class MonthlySummary
{
    /** @return array<string, int> */
    public function handle(Workspace $workspace, CarbonImmutable $month): array
    {
        $query = Transaction::query()
            ->where('workspace_id', $workspace->id)
            ->whereBetween('due_on', [$month->startOfMonth(), $month->endOfMonth()]);

        $income = $this->sumForType(clone $query, TransactionType::Income);
        $expense = $this->sumForType(clone $query, TransactionType::Expense);
        $realizedIncome = $this->sumForType((clone $query)->whereNotNull('settled_at'), TransactionType::Income);
        $realizedExpense = $this->sumForType((clone $query)->whereNotNull('settled_at'), TransactionType::Expense);

        return [
            'planned_income_minor' => $income,
            'planned_expense_minor' => $expense,
            'realized_balance_minor' => $realizedIncome - $realizedExpense,
            'forecast_balance_minor' => $income - $expense,
        ];
    }

    /** @param Builder<Transaction> $query */
    private function sumForType(Builder $query, TransactionType $type): int
    {
        return (int) $query->where('type', $type)->sum('amount_minor');
    }
}
