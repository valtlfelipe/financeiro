<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccountRequest;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('settings/Accounts', [
            'accounts' => AccountResource::collection(
                $request->user()->currentWorkspaceOrFail()->accounts()->orderBy('is_archived')->orderBy('name')->get(),
            )->resolve(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAccountRequest $request): RedirectResponse
    {
        $request->user()->currentWorkspaceOrFail()->accounts()->create($request->validated());

        return back()->with('toast', ['type' => 'success', 'message' => __('app.account.created')]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreAccountRequest $request, string $account): RedirectResponse
    {
        $this->account($request, $account)->update($request->validated());

        return back()->with('toast', ['type' => 'success', 'message' => __('app.account.updated')]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $account): RedirectResponse
    {
        $this->account($request, $account)->update(['is_archived' => true]);

        return back()->with('toast', ['type' => 'success', 'message' => __('app.account.archived')]);
    }

    private function account(Request $request, string $id): Account
    {
        return $request->user()->currentWorkspaceOrFail()->accounts()->findOrFail($id);
    }
}
