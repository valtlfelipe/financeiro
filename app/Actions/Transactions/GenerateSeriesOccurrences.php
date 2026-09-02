<?php

namespace App\Actions\Transactions;

use App\Models\TransactionSeries;
use App\RecurrenceFrequency;
use App\SeriesKind;
use Carbon\CarbonImmutable;

class GenerateSeriesOccurrences
{
    public function handle(TransactionSeries $series, ?CarbonImmutable $horizon = null): int
    {
        return $series->kind === SeriesKind::Installment
            ? $this->generateInstallments($series)
            : $this->generateRecurrences($series, $horizon ?? CarbonImmutable::today()->addMonths(12));
    }

    private function generateInstallments(TransactionSeries $series): int
    {
        $total = $series->total_occurrences ?? 0;
        $baseAmount = intdiv($series->amount_minor, $total);
        $remainder = $series->amount_minor % $total;
        $created = 0;

        for ($number = 1; $number <= $total; $number++) {
            $dueOn = $this->monthlyOccurrence($series, $number - 1);
            $transaction = $series->transactions()->firstOrCreate(
                ['occurrence_key' => sprintf('installment:%d', $number)],
                $this->occurrenceAttributes($series, $dueOn, $baseAmount + ($number <= $remainder ? 1 : 0), $number, $total),
            );

            $created += $transaction->wasRecentlyCreated ? 1 : 0;
        }

        return $created;
    }

    private function generateRecurrences(TransactionSeries $series, CarbonImmutable $horizon): int
    {
        $created = 0;
        $index = 0;

        while ($index < 1500) {
            $dueOn = $this->recurringOccurrence($series, $index);

            if ($dueOn->isAfter($horizon) || ($series->ends_on !== null && $dueOn->isAfter($series->ends_on))) {
                break;
            }

            $transaction = $series->transactions()->firstOrCreate(
                ['occurrence_key' => 'recurring:'.$dueOn->format('Y-m-d')],
                $this->occurrenceAttributes($series, $dueOn, $series->amount_minor),
            );

            $created += $transaction->wasRecentlyCreated ? 1 : 0;
            $index++;
        }

        return $created;
    }

    private function monthlyOccurrence(TransactionSeries $series, int $index): CarbonImmutable
    {
        return CarbonImmutable::parse($series->starts_on)
            ->addMonthsNoOverflow($index * $series->interval);
    }

    private function recurringOccurrence(TransactionSeries $series, int $index): CarbonImmutable
    {
        $start = CarbonImmutable::parse($series->starts_on);
        $steps = $index * $series->interval;

        return match ($series->frequency) {
            RecurrenceFrequency::Weekly => $start->addWeeks($steps),
            RecurrenceFrequency::Monthly => $start->addMonthsNoOverflow($steps),
            RecurrenceFrequency::Yearly => $start->addYearsNoOverflow($steps),
            default => $start,
        };
    }

    /** @return array<string, mixed> */
    private function occurrenceAttributes(
        TransactionSeries $series,
        CarbonImmutable $dueOn,
        int $amount,
        ?int $installmentNumber = null,
        ?int $installmentTotal = null,
    ): array {
        return [
            'workspace_id' => $series->workspace_id,
            'account_id' => $series->account_id,
            'destination_account_id' => $series->destination_account_id,
            'category_id' => $series->category_id,
            'type' => $series->transaction_type,
            'amount_minor' => $amount,
            'description' => $series->description,
            'due_on' => $dueOn,
            'notes' => $series->notes,
            'installment_number' => $installmentNumber,
            'installment_total' => $installmentTotal,
        ];
    }
}
