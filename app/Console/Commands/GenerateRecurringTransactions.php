<?php

namespace App\Console\Commands;

use App\Actions\Transactions\GenerateSeriesOccurrences;
use App\Models\TransactionSeries;
use App\SeriesKind;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

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
        $failed = 0;

        TransactionSeries::query()
            ->where('kind', SeriesKind::Recurring)
            ->where(fn ($query) => $query->whereNull('ends_on')->orWhere('ends_on', '>=', today()))
            ->eachById(function (TransactionSeries $series) use ($generator, &$created, &$failed): void {
                try {
                    $created += $generator->handle($series);
                } catch (Throwable $exception) {
                    report($exception);
                    $failed++;
                }
            });

        $this->info(trans_choice('app.recurring.generated', $created, ['count' => $created]));

        if ($failed > 0) {
            $this->error(trans_choice('app.recurring.failed', $failed, ['count' => $failed]));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
