<?php

namespace App\Models;

use App\TransactionType;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $workspace_id
 * @property int $account_id
 * @property int|null $destination_account_id
 * @property int|null $category_id
 * @property int|null $transaction_series_id
 * @property TransactionType $type
 * @property int $amount_minor
 * @property string $description
 * @property Carbon $due_on
 * @property Carbon|null $settled_at
 * @property string|null $notes
 * @property int|null $installment_number
 * @property int|null $installment_total
 * @property string|null $occurrence_key
 * @property-read Account $account
 * @property-read Account|null $destinationAccount
 * @property-read Category|null $category
 * @property-read TransactionSeries|null $series
 */
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workspace_id', 'account_id', 'destination_account_id', 'category_id',
        'transaction_series_id', 'type', 'amount_minor', 'description', 'due_on',
        'settled_at', 'notes', 'installment_number', 'installment_total', 'occurrence_key',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount_minor' => 'integer',
            'due_on' => 'date',
            'settled_at' => 'datetime',
            'installment_number' => 'integer',
            'installment_total' => 'integer',
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

    /** @return BelongsTo<TransactionSeries, $this> */
    public function series(): BelongsTo
    {
        return $this->belongsTo(TransactionSeries::class, 'transaction_series_id');
    }
}
