<?php

namespace App\Console\Commands;

use App\Actions\Transactions\GenerateSeriesOccurrences;
use App\Models\TransactionSeries;
use App\SeriesKind;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('financeiro:generate-recurring')]
#[Description('Mantém doze meses de lançamentos recorrentes futuros gerados')]
class GenerateRecurringTransactions extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(GenerateSeriesOccurrences $generator): int
    {
        $created = 0;

        TransactionSeries::query()
            ->where('kind', SeriesKind::Recurring)
            ->where(fn ($query) => $query->whereNull('ends_on')->orWhere('ends_on', '>=', today()))
            ->eachById(function (TransactionSeries $series) use ($generator, &$created): void {
                $created += $generator->handle($series);
            });

        $this->info(trans_choice('app.recurring.generated', $created, ['count' => $created]));

        return self::SUCCESS;
    }
}
