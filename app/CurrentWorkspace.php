<?php

namespace App;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;

class CurrentWorkspace
{
    public const SESSION_KEY = 'current_workspace_id';

    private ?Workspace $workspace = null;

    public function resolve(Request $request): ?Workspace
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return null;
        }

        $workspaceId = $request->session()->get(self::SESSION_KEY, $user->current_workspace_id);
        $workspace = is_numeric($workspaceId)
            ? $user->workspaces()->whereKey((int) $workspaceId)->first()
            : null;
        $usedFallback = $workspace === null;

        if ($workspace === null) {
            $workspace = $user->workspaces()->orderBy('workspaces.id')->first();
        }

        if ($workspace === null) {
            return null;
        }

        $this->workspace = $workspace;
        $user->setRelation('currentWorkspace', $workspace);
        $request->session()->put(self::SESSION_KEY, $workspace->id);

        if ($usedFallback && $user->current_workspace_id !== $workspace->id) {
            $user->update(['current_workspace_id' => $workspace->id]);
        }

        return $workspace;
    }

    public function get(): Workspace
    {
        abort_unless($this->workspace instanceof Workspace, 403);

        return $this->workspace;
    }

    public function select(Request $request, Workspace $workspace): void
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $this->workspace = $workspace;
        $user->setRelation('currentWorkspace', $workspace);
        $request->session()->put(self::SESSION_KEY, $workspace->id);
        $user->update(['current_workspace_id' => $workspace->id]);
    }
}
