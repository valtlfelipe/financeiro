<?php

namespace App\Http\Controllers;

use App\Actions\Transactions\MonthlySummary;
use App\Http\Resources\TransactionResource;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionSettlementController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $transaction, MonthlySummary $summary): JsonResponse
    {
        $validated = $request->validate(['settled' => ['required', 'boolean']]);
        $workspace = $request->user()->currentWorkspaceOrFail();
        $item = $workspace->transactions()->with(['account', 'destinationAccount', 'category', 'series'])->findOrFail($transaction);

        $item->update(['settled_at' => $validated['settled'] ? now() : null]);
        $item->refresh()->load(['account', 'destinationAccount', 'category', 'series']);

        return response()->json([
            'transaction' => TransactionResource::make($item)->resolve(),
            'summary' => $summary->handle($workspace, CarbonImmutable::parse($item->due_on)->startOfMonth()),
        ]);
    }
}
