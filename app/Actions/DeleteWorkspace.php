<?php

namespace App\Actions;

use App\MembershipRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteWorkspace
{
    public function handle(User $user, Workspace $workspace): Workspace
    {
        return DB::transaction(function () use ($user, $workspace): Workspace {
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($lockedUser->workspaces()->count() <= 1) {
                throw ValidationException::withMessages([
                    'confirmation' => __('app.workspace.cannot_delete_last'),
                ]);
            }

            $ownedWorkspace = $lockedUser->workspaces()
                ->whereKey($workspace->id)
                ->wherePivot('role', MembershipRole::Owner->value)
                ->firstOrFail();

            $ownedWorkspace->delete();

            return $lockedUser->workspaces()->orderBy('workspaces.id')->firstOrFail();
        });
    }
}
