<?php

namespace App\Http\Controllers;

use App\MembershipRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class MembershipController extends Controller
{
    public function destroy(Request $request, string $member): RedirectResponse
    {
        $workspace = $request->user()->currentWorkspaceOrFail();

        abort_unless($workspace->memberships()
            ->where('user_id', $request->user()->id)
            ->where('role', MembershipRole::Owner)
            ->exists(), 403);

        DB::transaction(function () use ($workspace, $member): void {
            $membership = $workspace->memberships()->where('user_id', $member)->lockForUpdate()->firstOrFail();

            abort_if($membership->role === MembershipRole::Owner, 403);

            $workspace->invitations()
                ->whereRaw('LOWER(email) = ?', [Str::lower($membership->user->email)])
                ->whereNull('accepted_at')
                ->delete();

            $membership->delete();

            User::query()->whereKey($member)
                ->where('current_workspace_id', $workspace->id)
                ->update(['current_workspace_id' => null]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('app.membership.removed')]);

        return to_route('invitations.index');
    }
}
