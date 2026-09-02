<?php

namespace App\Models;

use App\MembershipRole;
use Database\Factories\MembershipFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $workspace_id
 * @property int $user_id
 * @property MembershipRole $role
 * @property-read Workspace $workspace
 * @property-read User $user
 */
class Membership extends Model
{
    /** @use HasFactory<MembershipFactory> */
    use HasFactory;

    protected $fillable = ['workspace_id', 'user_id', 'role'];

    protected function casts(): array
    {
        return ['role' => MembershipRole::class];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
