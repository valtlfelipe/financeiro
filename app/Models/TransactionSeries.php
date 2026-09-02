<?php

namespace App\Models;

use App\RecurrenceFrequency;
use App\SeriesKind;
use App\TransactionType;
use Database\Factories\TransactionSeriesFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $workspace_id
 * @property int $account_id
 * @property int|null $destination_account_id
 * @property int|null $category_id
 * @property SeriesKind $kind
 * @property TransactionType $transaction_type
 * @property int $amount_minor
 * @property string $description
 * @property string|null $notes
 * @property RecurrenceFrequency|null $frequency
 * @property int $interval
 * @property Carbon $starts_on
 * @property Carbon|null $ends_on
 * @property int|null $total_occurrences
 * @property-read Collection<int, Transaction> $transactions
 */
class TransactionSeries extends Model
{
    /** @use HasFactory<TransactionSeriesFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workspace_id', 'account_id', 'destination_account_id', 'category_id', 'kind',
        'transaction_type', 'amount_minor', 'description', 'notes', 'frequency', 'interval',
        'starts_on', 'ends_on', 'total_occurrences',
    ];

    protected function casts(): array
    {
        return [
            'kind' => SeriesKind::class,
            'transaction_type' => TransactionType::class,
            'frequency' => RecurrenceFrequency::class,
            'amount_minor' => 'integer',
            'interval' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'total_occurrences' => 'integer',
        ];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /** @return BelongsTo<Account, $this> */
    public function destinationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'destination_account_id');
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasMany<Transaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
