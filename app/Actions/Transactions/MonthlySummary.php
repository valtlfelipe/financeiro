<?php

namespace App\Actions\Transactions;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\Workspace;
use App\Support\MinorAmount;
use App\TransactionType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MonthlySummary
{
    public function __construct(private AccountBalance $accountBalance) {}

    /** @return array<string, string> */
    public function handle(Workspace $workspace, CarbonImmutable $month): array
    {
        $month = $month->startOfMonth();
        $query = Transaction::query()
            ->where('workspace_id', $workspace->id)
            ->whereBetween('due_on', [$month->startOfMonth(), $month->endOfMonth()]);

        $income = $this->sumForType(clone $query, TransactionType::Income);
        $expense = $this->sumForType(clone $query, TransactionType::Expense);
        $today = $workspace->today();
        $period = match (true) {
            $month->endOfMonth()->isBefore($today) => 'past',
            $month->isAfter($today->startOfMonth()) => 'future',
            default => 'current',
        };
        $accounts = $workspace->accounts()->get();
        $openingBalance = $this->projectedBalance($accounts, $month->subDay());
        $forecastBalance = $this->projectedBalance($accounts, $month->endOfMonth());
        $realizedBalance = $period === 'past'
            ? $this->settledBalance($accounts, $month->endOfMonth())
            : $this->currentBalance($accounts);

        return [
            'planned_income_minor' => $income,
            'planned_expense_minor' => $expense,
            'opening_balance_minor' => $openingBalance,
            'forecast_change_minor' => MinorAmount::subtract($forecastBalance, $openingBalance),
            'realized_balance_minor' => $realizedBalance,
            'forecast_balance_minor' => $forecastBalance,
            'period' => $period,
        ];
    }

    /** @param Collection<int, Account> $accounts */
    private function projectedBalance(Collection $accounts, CarbonImmutable $date): string
    {
        return MinorAmount::add(...$accounts
            ->map(fn (Account $account): string => $this->accountBalance->projectedThrough($account, $date))
            ->all());
    }

    /** @param Collection<int, Account> $accounts */
    private function settledBalance(Collection $accounts, CarbonImmutable $date): string
    {
        return MinorAmount::add(...$accounts
            ->map(fn (Account $account): string => $this->accountBalance->settledThrough($account, $date))
            ->all());
    }

    /** @param Collection<int, Account> $accounts */
    private function currentBalance(Collection $accounts): string
    {
        return MinorAmount::add(...$accounts
            ->map(fn (Account $account): string => $this->accountBalance->handle($account))
            ->all());
    }

    /** @param Builder<Transaction> $query */
    private function sumForType(Builder $query, TransactionType $type): string
    {
        $total = $query->where('type', $type)->sum('amount_minor');

        return MinorAmount::normalize(is_int($total) || is_string($total) ? $total : (string) $total);
    }
}
