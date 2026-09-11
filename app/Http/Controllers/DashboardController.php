<?php

namespace App\Http\Controllers;

use App\Actions\Transactions\AccountBalance;
use App\Http\Resources\AccountResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\TransactionResource;
use App\Models\Account;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, AccountBalance $accountBalance): Response
    {
        $workspace = $request->user()->currentWorkspaceOrFail();
        $month = $this->month($request->string('month')->toString(), $workspace->timezone);
        $transactionLimit = 6;
        $today = $workspace->today();
        $overdueTransactions = $workspace->transactions()
            ->with(['account', 'destinationAccount', 'category', 'series'])
            ->whereNull('settled_at')
            ->whereDate('due_on', '<', $today);
        $overdueTransactionsCount = (clone $overdueTransactions)->count();
        $overdue = $overdueTransactions
            ->orderBy('due_on')
            ->orderBy('id')
            ->limit($transactionLimit)
            ->get();
        $pendingTransactions = $workspace->transactions()
            ->with(['account', 'destinationAccount', 'category', 'series'])
            ->whereBetween('due_on', [$month->startOfMonth(), $month->endOfMonth()])
            ->whereDate('due_on', '>=', $today)
            ->whereNull('settled_at');
        $pendingTransactionsCount = (clone $pendingTransactions)->count();
        $transactions = $pendingTransactions
            ->orderBy('due_on')
            ->limit($transactionLimit)
            ->get();

        return Inertia::render('Dashboard', [
            'month' => $month->format('Y-m'),
            'accounts' => $accountBalance->currentAccounts($workspace)
                ->map(fn (Account $account): array => [
                    'id' => $account->id,
                    'name' => $account->name,
                    'color' => $account->color,
                    'balanceMinor' => (string) $account->getAttribute('balance_minor'),
                ]),
            'recentTransactions' => TransactionResource::collection($transactions)->resolve(),
            'remainingTransactionsCount' => max($pendingTransactionsCount - $transactions->count(), 0),
            'overdueTransactions' => TransactionResource::collection($overdue)->resolve(),
            'overdueTransactionsCount' => $overdueTransactionsCount,
            'formAccounts' => AccountResource::collection(
                $workspace->accounts()->where('is_archived', false)->orderBy('name')->get(),
            )->resolve(),
            'categories' => CategoryResource::collection(
                $workspace->categories()->where('is_archived', false)->orderBy('name')->get(),
            )->resolve(),
        ]);
    }

    private function month(string $month, string $timezone): CarbonImmutable
    {
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) !== 1) {
            return CarbonImmutable::today($timezone)->startOfMonth();
        }

        return CarbonImmutable::createFromFormat('!Y-m', $month, $timezone)
            ?: CarbonImmutable::today($timezone)->startOfMonth();
    }
}
