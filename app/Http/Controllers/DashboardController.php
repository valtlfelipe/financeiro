<?php

namespace App\Http\Controllers;

use App\Actions\Transactions\AccountBalance;
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
        $transactions = $workspace->transactions()
            ->with(['account', 'destinationAccount', 'category', 'series'])
            ->whereBetween('due_on', [$month->startOfMonth(), $month->endOfMonth()])
            ->whereNull('settled_at')
            ->orderBy('due_on')
            ->limit(6)
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
