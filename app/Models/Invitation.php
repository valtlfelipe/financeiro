<?php

namespace App\Models;

use App\MembershipRole;
use Database\Factories\InvitationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $workspace_id
 * @property int $invited_by
 * @property string $email
 * @property MembershipRole $role
 * @property string $token_hash
 * @property Carbon $expires_at
 * @property Carbon|null $accepted_at
 * @property-read Workspace $workspace
 * @property-read User $inviter
 */
class Invitation extends Model
{
    /** @use HasFactory<InvitationFactory> */
    use HasFactory;

    protected $fillable = [
        'workspace_id', 'invited_by', 'email', 'role', 'token_hash', 'expires_at', 'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'role' => MembershipRole::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<User, $this> */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
