<?php

namespace App\Http\Controllers;

use App\Actions\Transactions\CreateTransactions;
use App\Actions\Transactions\MonthlySummary;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Http\Resources\AccountResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\SeriesKind;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, MonthlySummary $summary): Response
    {
        $workspace = $request->user()->currentWorkspaceOrFail();
        $month = $this->month($request->string('month')->toString(), $workspace->timezone);
        $query = $workspace->transactions()
            ->with(['account', 'destinationAccount', 'category', 'series'])
            ->whereBetween('due_on', [$month->startOfMonth(), $month->endOfMonth()]);

        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\\%', '\\_'], $request->string('search')->toString());
            $query->where('description', 'like', '%'.$search.'%');
        }

        foreach (['account_id', 'category_id', 'type'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        match ($request->string('status')->toString()) {
            'settled' => $query->whereNotNull('settled_at'),
            'pending' => $query->whereNull('settled_at'),
            default => null,
        };

        return Inertia::render('Transactions/Index', [
            'month' => $month->format('Y-m'),
            'summary' => $summary->handle($workspace, $month),
            'transactions' => TransactionResource::collection($query->orderBy('due_on')->orderBy('id')->get())->resolve(),
            'accounts' => AccountResource::collection($workspace->accounts()->where('is_archived', false)->orderBy('name')->get())->resolve(),
            'filterAccounts' => AccountResource::collection($workspace->accounts()->orderBy('is_archived')->orderBy('name')->get())->resolve(),
            'categories' => CategoryResource::collection($workspace->categories()->where('is_archived', false)->orderBy('name')->get())->resolve(),
            'filters' => $request->only(['search', 'account_id', 'category_id', 'type', 'status']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTransactionRequest $request, CreateTransactions $creator): RedirectResponse
    {
        $creator->handle($request->user()->currentWorkspaceOrFail(), $request->validated());

        return back()->with('toast', ['type' => 'success', 'message' => __('app.transaction.created')]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTransactionRequest $request, string $transaction): RedirectResponse
    {
        $item = $this->transaction($request, $transaction);
        $request->validate([
            'scope' => [$item->series === null ? 'sometimes' : 'required', 'in:single,future'],
        ]);
        $this->guardArchivedAccountHistory($request, $item);
        $originalDueOn = $item->due_on->toDateString();
        $originalAmount = $item->amount_minor;
        $attributes = [
            ...Arr::only($request->validated(), [
                'account_id', 'destination_account_id', 'category_id', 'type',
                'amount_minor', 'description', 'due_on', 'notes',
            ]),
            'settled_at' => $request->boolean('settled') ? ($item->settled_at ?? now()) : null,
        ];

        DB::transaction(function () use ($item, $attributes, $request, $originalDueOn, $originalAmount): void {
            $item->update($attributes);

            if ($request->string('scope')->toString() !== 'future' || $item->series === null) {
                return;
            }

            $seriesAttributes = [
                'account_id' => $attributes['account_id'],
                'destination_account_id' => $attributes['destination_account_id'] ?? null,
                'category_id' => $attributes['category_id'] ?? null,
                'transaction_type' => $attributes['type'],
                'amount_minor' => $attributes['amount_minor'],
                'description' => $attributes['description'],
                'notes' => $attributes['notes'] ?? null,
            ];

            if ($item->series->kind === SeriesKind::Installment) {
                $seriesAttributes = Arr::except($seriesAttributes, ['amount_minor']);
            }

            $item->series->update($seriesAttributes);
            $futureAttributes = Arr::except($attributes, ['due_on', 'settled_at']);

            if ($item->series->kind === SeriesKind::Installment && $attributes['amount_minor'] === $originalAmount) {
                $futureAttributes = Arr::except($futureAttributes, ['amount_minor']);
            }

            $item->series->transactions()
                ->whereDate('due_on', '>', $originalDueOn)
                ->whereNull('settled_at')
                ->update($futureAttributes);

            if ($item->series->kind === SeriesKind::Installment) {
                $item->series->update([
                    'amount_minor' => (int) $item->series->transactions()->sum('amount_minor'),
                ]);
            }
        });

        return back()->with('toast', ['type' => 'success', 'message' => __('app.transaction.updated')]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $transaction): RedirectResponse
    {
        $item = $this->transaction($request, $transaction);
        $request->validate([
            'scope' => [$item->series === null ? 'sometimes' : 'required', 'in:single,future'],
        ]);

        DB::transaction(function () use ($item, $request): void {
            $item->delete();

            if ($request->string('scope')->toString() === 'future' && $item->series !== null) {
                if ($item->series->kind === SeriesKind::Recurring) {
                    $item->series->update([
                        'ends_on' => $item->due_on->copy()->subDay(),
                    ]);
                }

                $item->series->transactions()
                    ->whereDate('due_on', '>', $item->due_on)
                    ->whereNull('settled_at')
                    ->delete();
            }
        });

        return back()->with('toast', ['type' => 'success', 'message' => __('app.transaction.deleted')]);
    }

    private function transaction(Request $request, string $id): Transaction
    {
        return $request->user()->currentWorkspaceOrFail()->transactions()
            ->with(['account', 'destinationAccount', 'series'])
            ->findOrFail($id);
    }

    private function guardArchivedAccountHistory(UpdateTransactionRequest $request, Transaction $item): void
    {
        if (! $item->account->is_archived && ! $item->destinationAccount?->is_archived) {
            return;
        }

        $financialFieldsChanged = $request->integer('account_id') !== $item->account_id
            || ($request->input('destination_account_id') === null ? null : $request->integer('destination_account_id')) !== $item->destination_account_id
            || $request->string('type')->toString() !== $item->type->value
            || $request->integer('amount_minor') !== $item->amount_minor
            || CarbonImmutable::parse($request->string('due_on')->toString())->toDateString() !== $item->due_on->toDateString()
            || $request->boolean('settled') !== ($item->settled_at !== null);

        if ($financialFieldsChanged) {
            throw ValidationException::withMessages([
                'account_id' => __('app.account.archived_history_locked'),
            ]);
        }
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
