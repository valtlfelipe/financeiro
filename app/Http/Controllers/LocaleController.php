<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLocaleRequest;
use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(UpdateLocaleRequest $request): RedirectResponse
    {
        $request->user()->update(['locale' => $request->validated('locale')]);

        return back()->with('toast', ['type' => 'success', 'message' => __('app.locale.updated')]);
    }
}
