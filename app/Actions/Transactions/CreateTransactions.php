<?php

namespace App\Actions\Transactions;

use App\Models\Transaction;
use App\Models\Workspace;
use App\SeriesKind;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateTransactions
{
    public function __construct(private readonly GenerateSeriesOccurrences $generator) {}

    /** @param array<string, mixed> $attributes */
    public function handle(Workspace $workspace, array $attributes): Transaction
    {
        return DB::transaction(function () use ($workspace, $attributes): Transaction {
            $kind = isset($attributes['series_kind'])
                ? SeriesKind::from($attributes['series_kind'])
                : null;

            if ($kind === null) {
                return $workspace->transactions()->create($this->transactionAttributes($attributes));
            }

            $series = $workspace->transactionSeries()->create([
                'account_id' => $attributes['account_id'],
                'destination_account_id' => $attributes['destination_account_id'] ?? null,
                'category_id' => $attributes['category_id'] ?? null,
                'kind' => $kind,
                'transaction_type' => $attributes['type'],
                'amount_minor' => $attributes['amount_minor'],
                'description' => $attributes['description'],
                'notes' => $attributes['notes'] ?? null,
                'frequency' => $attributes['frequency'] ?? null,
                'interval' => 1,
                'starts_on' => $attributes['due_on'],
                'ends_on' => $attributes['ends_on'] ?? null,
                'total_occurrences' => $attributes['installments'] ?? null,
            ]);

            $this->generator->handle($series);

            $first = $series->transactions()->oldest('due_on')->firstOrFail();

            if (($attributes['settled'] ?? false) === true) {
                $first->update(['settled_at' => now()]);
            }

            return $first->fresh(['account', 'destinationAccount', 'category', 'series']) ?? $first;
        });
    }

    /** @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function transactionAttributes(array $attributes): array
    {
        return [
            ...Arr::only($attributes, [
                'account_id', 'destination_account_id', 'category_id', 'type',
                'amount_minor', 'description', 'due_on', 'notes',
            ]),
            'settled_at' => ($attributes['settled'] ?? false)
                ? CarbonImmutable::now()
                : null,
        ];
    }
}
