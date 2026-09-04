<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->createSqliteTriggers();

            return;
        }

        foreach ($this->constraints() as $table => $constraints) {
            foreach ($constraints as $name => $expression) {
                DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$name} CHECK ({$expression})");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS transactions_financial_integrity_insert');
            DB::unprepared('DROP TRIGGER IF EXISTS transactions_financial_integrity_update');
            DB::unprepared('DROP TRIGGER IF EXISTS transaction_series_financial_integrity_insert');
            DB::unprepared('DROP TRIGGER IF EXISTS transaction_series_financial_integrity_update');

            return;
        }

        foreach ($this->constraints() as $table => $constraints) {
            foreach (array_keys($constraints) as $name) {
                DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$name}");
            }
        }
    }

    /** @return array<string, array<string, string>> */
    private function constraints(): array
    {
        return [
            'transactions' => [
                'transactions_amount_positive' => 'amount_minor > 0',
                'transactions_type_valid' => "type IN ('income', 'expense', 'transfer')",
                'transactions_shape_valid' => <<<'SQL'
                    (
                        type = 'transfer'
                        AND destination_account_id IS NOT NULL
                        AND destination_account_id <> account_id
                        AND category_id IS NULL
                    ) OR (
                        type IN ('income', 'expense')
                        AND destination_account_id IS NULL
                    )
                    SQL,
                'transactions_installment_shape_valid' => <<<'SQL'
                    (
                        installment_number IS NULL
                        AND installment_total IS NULL
                    ) OR (
                        transaction_series_id IS NOT NULL
                        AND installment_number IS NOT NULL
                        AND installment_total IS NOT NULL
                        AND installment_total BETWEEN 2 AND 120
                        AND installment_number BETWEEN 1 AND installment_total
                    )
                    SQL,
            ],
            'transaction_series' => [
                'transaction_series_amount_positive' => 'amount_minor > 0',
                'transaction_series_type_valid' => "transaction_type IN ('income', 'expense', 'transfer')",
                'transaction_series_transfer_shape_valid' => <<<'SQL'
                    (
                        transaction_type = 'transfer'
                        AND destination_account_id IS NOT NULL
                        AND destination_account_id <> account_id
                        AND category_id IS NULL
                    ) OR (
                        transaction_type IN ('income', 'expense')
                        AND destination_account_id IS NULL
                    )
                    SQL,
                'transaction_series_dates_valid' => 'ends_on IS NULL OR ends_on >= starts_on',
                'transaction_series_shape_valid' => <<<'SQL'
                    (
                        kind = 'recurring'
                        AND frequency IN ('weekly', 'monthly', 'yearly')
                        AND total_occurrences IS NULL
                        AND interval >= 1
                    ) OR (
                        kind = 'installment'
                        AND frequency IS NULL
                        AND total_occurrences BETWEEN 2 AND 120
                        AND amount_minor >= total_occurrences
                        AND interval >= 1
                    )
                    SQL,
            ],
        ];
    }

    private function createSqliteTriggers(): void
    {
        $constraints = $this->constraints();
        $this->createSqliteIntegrityTriggers(
            'transactions',
            'transactions_financial_integrity',
            $this->sqliteExpression($constraints['transactions'], [
                'amount_minor', 'type', 'destination_account_id', 'account_id', 'category_id',
                'installment_number', 'installment_total', 'transaction_series_id',
            ]),
        );
        $this->createSqliteIntegrityTriggers(
            'transaction_series',
            'transaction_series_financial_integrity',
            $this->sqliteExpression($constraints['transaction_series'], [
                'amount_minor', 'transaction_type', 'destination_account_id', 'account_id',
                'category_id', 'ends_on', 'starts_on', 'kind', 'frequency',
                'total_occurrences', 'interval',
            ]),
        );
    }

    /**
     * @param  array<string, string>  $constraints
     * @param  list<string>  $columns
     */
    private function sqliteExpression(array $constraints, array $columns): string
    {
        $expression = implode(' AND ', array_map(
            fn (string $constraint): string => "({$constraint})",
            $constraints,
        ));

        return preg_replace(
            '/\b('.implode('|', $columns).')\b/',
            'NEW.$1',
            $expression,
        ) ?? $expression;
    }

    private function createSqliteIntegrityTriggers(string $table, string $name, string $expression): void
    {
        foreach (['INSERT', 'UPDATE'] as $operation) {
            $suffix = strtolower($operation);
            DB::connection()->getPdo()->exec(<<<SQL
                CREATE TRIGGER {$name}_{$suffix}
                BEFORE {$operation} ON {$table}
                WHEN NOT ({$expression})
                BEGIN
                    SELECT RAISE(ABORT, 'financial integrity constraint failed');
                END
                SQL);
        }
    }
};
