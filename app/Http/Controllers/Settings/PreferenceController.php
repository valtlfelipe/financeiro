<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdatePreferencesRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class PreferenceController extends Controller
{
    public function update(UpdatePreferencesRequest $request): RedirectResponse
    {
        $request->user()->currentWorkspaceOrFail()->update([
            'name' => $request->validated('workspace_name'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('app.workspace.updated')]);

        return to_route('preferences.edit');
    }
}
