<?php

namespace App\Models;

use App\AccountType;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $workspace_id
 * @property string $name
 * @property AccountType $type
 * @property int $initial_balance_minor
 * @property Carbon $balance_date
 * @property string|null $icon
 * @property string $color
 * @property bool $is_archived
 */
class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory;

    protected $fillable = [
        'workspace_id', 'name', 'type', 'initial_balance_minor', 'balance_date',
        'icon', 'color', 'is_archived',
    ];

    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'initial_balance_minor' => 'integer',
            'balance_date' => 'date',
            'is_archived' => 'boolean',
        ];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return HasMany<Transaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /** @return HasMany<Transaction, $this> */
    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(Transaction::class, 'destination_account_id');
    }
}
