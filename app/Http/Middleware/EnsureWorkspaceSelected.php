<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspaceSelected
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if ($user->current_workspace_id === null) {
            $workspaceId = $user->workspaces()->value('workspaces.id');

            if ($workspaceId === null) {
                return redirect()->route('setup.create');
            }

            $user->update(['current_workspace_id' => $workspaceId]);
        }

        $belongsToWorkspace = $user->workspaces()
            ->whereKey($user->current_workspace_id)
            ->exists();

        if (! $belongsToWorkspace) {
            abort(403);
        }

        return $next($request);
    }
}
