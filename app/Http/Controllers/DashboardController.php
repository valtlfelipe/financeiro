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
        $month = $this->month($request->string('month')->toString());
        $transactions = $workspace->transactions()
            ->with(['account', 'destinationAccount', 'category', 'series'])
            ->whereBetween('due_on', [$month->startOfMonth(), $month->endOfMonth()])
            ->whereNull('settled_at')
            ->orderBy('due_on')
            ->limit(6)
            ->get();

        return Inertia::render('Dashboard', [
            'month' => $month->format('Y-m'),
            'accounts' => $workspace->accounts()->where('is_archived', false)->orderBy('id')->get()
                ->map(fn (Account $account): array => [
                    'id' => $account->id,
                    'name' => $account->name,
                    'color' => $account->color,
                    'balanceMinor' => $accountBalance->handle($account),
                ]),
            'recentTransactions' => TransactionResource::collection($transactions)->resolve(),
        ]);
    }

    private function month(string $month): CarbonImmutable
    {
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) !== 1) {
            return CarbonImmutable::today()->startOfMonth();
        }

        return CarbonImmutable::createFromFormat('!Y-m', $month) ?: CarbonImmutable::today()->startOfMonth();
    }
}
