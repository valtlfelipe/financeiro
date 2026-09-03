<?php

namespace App\Http\Middleware;

use App\CurrentWorkspace;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspaceContext
{
    public function __construct(private CurrentWorkspace $currentWorkspace) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe() || ! $this->changesWorkspaceData($request)) {
            return $next($request);
        }

        $expectedWorkspaceId = $request->header('X-Workspace-Id');

        if (! is_string($expectedWorkspaceId) || ! hash_equals((string) $this->currentWorkspace->get()->id, $expectedWorkspaceId)) {
            return response()->json([
                'code' => 'workspace_context_changed',
                'message' => __('app.workspace.context_changed'),
            ], 409)->header('X-Workspace-Context-Changed', 'true');
        }

        return $next($request);
    }

    private function changesWorkspaceData(Request $request): bool
    {
        $routeName = $request->route()?->getName();

        return is_string($routeName) && Str::is([
            'accounts.*', 'categories.*', 'transactions.*',
            'invitations.*', 'members.*', 'preferences.*',
        ], $routeName);
    }
}
