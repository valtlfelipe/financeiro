<?php

namespace App\Http\Controllers;

use App\AccountType;
use App\CategoryType;
use App\Http\Requests\SetupRequest;
use App\Models\User;
use App\Models\Workspace;
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

    public function store(SetupRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $user = User::query()->create([
                'name' => $request->string('name'),
                'email' => $request->string('email'),
                'password' => $request->string('password'),
                'locale' => config('locales.default'),
            ]);

            $workspace = Workspace::query()->create([
                'name' => $request->string('workspace_name'),
                'currency_code' => 'BRL',
                'timezone' => 'America/Sao_Paulo',
            ]);
            $workspace->addOwner($user);

            $workspace->accounts()->create([
                'name' => __('app.defaults.account'),
                'type' => AccountType::Checking,
                'initial_balance_minor' => 0,
                'balance_date' => today(),
                'icon' => 'wallet-cards',
                'color' => '#148A62',
            ]);

            foreach ([
                [__('app.defaults.income_category'), CategoryType::Income, 'briefcase-business', '#148A62'],
                [__('app.defaults.expense_category'), CategoryType::Expense, 'shopping-bag', '#C84D57'],
            ] as [$name, $type, $icon, $color]) {
                $workspace->categories()->create(compact('name', 'type', 'icon', 'color'));
            }

            $user->update(['current_workspace_id' => $workspace->id]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
