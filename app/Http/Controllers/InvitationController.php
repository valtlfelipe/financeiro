<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcceptInvitationRequest;
use App\Http\Requests\StoreInvitationRequest;
use App\MembershipRole;
use App\Models\Invitation;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    public function index(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspaceOrFail();

        abort_unless($this->isOwner($request), 403);

        return Inertia::render('settings/Members', [
            'members' => $workspace->memberships()->with('user')->get()->sortBy('user.name')->values()->map(fn (Membership $membership): array => [
                'id' => $membership->user->id,
                'name' => $membership->user->name,
                'email' => $membership->user->email,
                'role' => $membership->role,
            ]),
            'pendingInvitations' => $workspace->invitations()
                ->whereNull('accepted_at')->where('expires_at', '>', now())->latest()->get()
                ->map(fn (Invitation $invitation): array => [
                    'id' => $invitation->id,
                    'email' => $invitation->email,
                    'expiresAt' => $invitation->expires_at->toIso8601String(),
                ]),
            'invitationUrl' => $request->session()->get('invitation_url'),
        ]);
    }

    public function store(StoreInvitationRequest $request): RedirectResponse
    {
        $token = Str::random(64);
        $workspace = $request->user()->currentWorkspaceOrFail();

        $workspace->invitations()->create([
            'invited_by' => $request->user()->id,
            'email' => Str::lower($request->string('email')->toString()),
            'role' => MembershipRole::Member,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(7),
        ]);

        return back()
            ->with('invitation_url', route('invitations.show', ['token' => $token]))
            ->with('toast', ['type' => 'success', 'message' => __('app.invitation.created')]);
    }

    public function show(string $token): Response
    {
        $invitation = $this->invitation($token);
        $existingUser = User::query()->where('email', $invitation->email)->exists();

        return Inertia::render('Invitations/Show', [
            'token' => $token,
            'email' => $invitation->email,
            'workspaceName' => $invitation->workspace->name,
            'existingUser' => $existingUser,
        ]);
    }

    public function accept(AcceptInvitationRequest $request, string $token): RedirectResponse
    {
        $invitation = $request->invitation();
        abort_if($invitation === null, 404);

        $user = DB::transaction(function () use ($request, $invitation): User {
            $user = User::query()->where('email', $invitation->email)->first();

            if ($user !== null && ! Hash::check($request->string('password'), $user->password)) {
                throw ValidationException::withMessages(['password' => __('auth.failed')]);
            }

            if ($user === null) {
                $user = User::query()->create([
                    'name' => $request->string('name'),
                    'email' => $invitation->email,
                    'password' => $request->string('password'),
                    'locale' => config('locales.default'),
                ]);
            }

            Membership::query()->firstOrCreate([
                'workspace_id' => $invitation->workspace_id,
                'user_id' => $user->id,
            ], ['role' => $invitation->role]);
            $user->update(['current_workspace_id' => $invitation->workspace_id]);
            $invitation->update(['accepted_at' => now()]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    private function invitation(string $token): Invitation
    {
        return Invitation::query()
            ->with('workspace')
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->firstOrFail();
    }

    private function isOwner(Request $request): bool
    {
        return $request->user()->workspaces()
            ->whereKey($request->user()->current_workspace_id)
            ->wherePivot('role', MembershipRole::Owner->value)
            ->exists();
    }
}
