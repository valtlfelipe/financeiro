<?php

namespace App\Http\Middleware;

use App\CurrentWorkspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspaceSelected
{
    public function __construct(private CurrentWorkspace $currentWorkspace) {}

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

        abort_if($this->currentWorkspace->resolve($request) === null, 403);

        return $next($request);
    }
}
