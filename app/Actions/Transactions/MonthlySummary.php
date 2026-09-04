<?php

namespace App\Actions\Transactions;

use App\Models\Transaction;
use App\Models\Workspace;
use App\Support\MinorAmount;
use App\TransactionType;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class MonthlySummary
{
    public function __construct(private AccountBalance $accountBalance) {}

    /** @return array<string, string> */
    public function handle(Workspace $workspace, CarbonImmutable $month): array
    {
        $month = $month->startOfMonth();
        $today = $workspace->today();
        $period = match (true) {
            $month->endOfMonth()->isBefore($today) => 'past',
            $month->isAfter($today->startOfMonth()) => 'future',
            default => 'current',
        };

        return DB::transaction(function () use ($workspace, $month, $today, $period): array {
            if (DB::getDriverName() === 'pgsql' && DB::transactionLevel() === 1) {
                DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            }

            $totals = Transaction::query()
                ->where('workspace_id', $workspace->id)
                ->whereBetween('due_on', [$month->startOfMonth(), $month->endOfMonth()])
                ->toBase()
                ->selectRaw(
                    'COALESCE(SUM(CASE WHEN type = ? THEN amount_minor ELSE 0 END), 0) AS income_minor',
                    [TransactionType::Income->value],
                )
                ->selectRaw(
                    'COALESCE(SUM(CASE WHEN type = ? THEN amount_minor ELSE 0 END), 0) AS expense_minor',
                    [TransactionType::Expense->value],
                )
                ->first();
            $positions = $this->accountBalance->monthlyPositions(
                $workspace,
                $month->subDay(),
                $month->endOfMonth(),
                $period === 'past' ? $month->endOfMonth() : $today,
            );
            $income = $totals?->income_minor;
            $expense = $totals?->expense_minor;

            return [
                'planned_income_minor' => $this->normalizeTotal($income),
                'planned_expense_minor' => $this->normalizeTotal($expense),
                'opening_balance_minor' => $positions['opening'],
                'forecast_change_minor' => MinorAmount::subtract($positions['forecast'], $positions['opening']),
                'realized_balance_minor' => $positions['realized'],
                'forecast_balance_minor' => $positions['forecast'],
                'period' => $period,
            ];
        }, attempts: 3);
    }

    private function normalizeTotal(mixed $total): string
    {
        return is_int($total) || is_string($total) ? MinorAmount::normalize($total) : '0';
    }
}
