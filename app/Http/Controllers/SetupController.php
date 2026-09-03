<?php

namespace App\Http\Controllers;

use App\Actions\CreateWorkspace;
use App\CurrentWorkspace;
use App\Http\Requests\SetupRequest;
use App\Models\User;
use App\WorkspaceIcon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SetupController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        if (User::query()->exists()) {
            return redirect()->route('login');
        }

        return Inertia::render('Setup');
    }

    public function store(SetupRequest $request, CreateWorkspace $createWorkspace, CurrentWorkspace $currentWorkspace): RedirectResponse
    {
        $user = DB::transaction(function () use ($request, $createWorkspace): User {
            $user = User::query()->create([
                'name' => $request->string('name'),
                'email' => $request->string('email'),
                'password' => $request->string('password'),
                'locale' => config('locales.default'),
            ]);

            $workspace = $createWorkspace->handle(
                $user,
                $request->string('workspace_name')->toString(),
                WorkspaceIcon::from($request->string('icon')->toString()),
            );

            $user->update(['current_workspace_id' => $workspace->id]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();
        $currentWorkspace->select($request, $user->currentWorkspaceOrFail());

        return redirect()->route('dashboard');
    }
}
