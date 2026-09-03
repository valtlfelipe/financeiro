<?php

namespace App\Http\Controllers;

use App\Actions\CreateWorkspace;
use App\CurrentWorkspace;
use App\Http\Requests\StoreWorkspaceRequest;
use App\Http\Requests\SwitchWorkspaceRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class WorkspaceController extends Controller
{
    public function store(StoreWorkspaceRequest $request, CreateWorkspace $createWorkspace, CurrentWorkspace $currentWorkspace): RedirectResponse
    {
        $workspace = $createWorkspace->handle(
            $request->user(),
            $request->string('workspace_name')->toString(),
        );
        $currentWorkspace->select($request, $workspace);

        Inertia::clearHistory();
        Inertia::flash('toast', ['type' => 'success', 'message' => __('app.workspace.created')]);

        return to_route('dashboard');
    }

    public function update(SwitchWorkspaceRequest $request, CurrentWorkspace $currentWorkspace): RedirectResponse
    {
        $workspace = $request->user()->workspaces()
            ->whereKey($request->integer('workspace_id'))
            ->firstOrFail();

        $currentWorkspace->select($request, $workspace);
        Inertia::clearHistory();
        Inertia::flash('toast', ['type' => 'success', 'message' => __('app.workspace.switched')]);

        return to_route('dashboard');
    }
}
