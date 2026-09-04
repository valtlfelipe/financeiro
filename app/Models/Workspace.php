<?php

namespace App\Models;

use App\MembershipRole;
use Carbon\CarbonImmutable;
use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $name
 * @property string $icon
 * @property string $currency_code
 * @property string $timezone
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, User> $users
 * @property-read Collection<int, Account> $accounts
 * @property-read Collection<int, Category> $categories
 * @property-read Collection<int, Transaction> $transactions
 * @property-read Collection<int, TransactionSeries> $transactionSeries
 * @property-read Collection<int, Invitation> $invitations
 */
class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory;

    protected $fillable = ['name', 'icon', 'currency_code', 'timezone'];

    public function today(): CarbonImmutable
    {
        return CarbonImmutable::today($this->timezone);
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'memberships')
            ->withPivot('role')
            ->withTimestamps();
    }

    /** @return HasMany<Membership, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /** @return HasMany<Account, $this> */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /** @return HasMany<Category, $this> */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /** @return HasMany<Transaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /** @return HasMany<TransactionSeries, $this> */
    public function transactionSeries(): HasMany
    {
        return $this->hasMany(TransactionSeries::class);
    }

    /** @return HasMany<Invitation, $this> */
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function addOwner(User $user): void
    {
        $this->users()->syncWithoutDetaching([
            $user->id => ['role' => MembershipRole::Owner->value],
        ]);
    }
}
