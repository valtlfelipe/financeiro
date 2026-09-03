<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\Settings\AboutController;
use App\Http\Controllers\Settings\PreferenceController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransactionSettlementController;
use App\Http\Controllers\WorkspaceController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => User::query()->exists()
    ? redirect()->route(auth()->check() ? 'dashboard' : 'login')
    : redirect()->route('setup.create'))->name('home');

Route::middleware('guest')->group(function () {
    Route::get('setup', [SetupController::class, 'create'])->name('setup.create');
    Route::post('setup', [SetupController::class, 'store'])->middleware('throttle:5,1')->name('setup.store');
});

Route::get('invitations/{token}', [InvitationController::class, 'show'])->name('invitations.show');
Route::post('invitations/{token}', [InvitationController::class, 'accept'])
    ->middleware('throttle:5,1')->name('invitations.accept');

Route::middleware(['auth', 'workspace', 'workspace.context'])->group(function () {
    Route::post('workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');
    Route::patch('workspaces/current', [WorkspaceController::class, 'update'])->name('workspaces.switch');
    Route::delete('workspaces/current', [WorkspaceController::class, 'destroy'])->name('workspaces.destroy');
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::patch('transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
    Route::delete('transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
    Route::patch('transactions/{transaction}/settlement', TransactionSettlementController::class)
        ->middleware('throttle:30,1')->name('transactions.settlement');

    Route::get('settings/accounts', [AccountController::class, 'index'])->name('accounts.index');
    Route::post('settings/accounts', [AccountController::class, 'store'])->name('accounts.store');
    Route::patch('settings/accounts/{account}', [AccountController::class, 'update'])->name('accounts.update');
    Route::delete('settings/accounts/{account}', [AccountController::class, 'destroy'])->name('accounts.destroy');

    Route::get('settings/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('settings/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::patch('settings/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('settings/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('settings/members', [InvitationController::class, 'index'])->name('invitations.index');
    Route::post('settings/invitations', [InvitationController::class, 'store'])
        ->middleware('throttle:10,1')->name('invitations.store');
    Route::delete('settings/invitations/{invitation}', [InvitationController::class, 'destroy'])->name('invitations.destroy');
    Route::delete('settings/members/{member}', [MembershipController::class, 'destroy'])->name('members.destroy');
    Route::get('settings/preferences', fn () => Inertia::render('settings/Preferences'))->name('preferences.edit');
    Route::patch('settings/preferences', [PreferenceController::class, 'update'])->name('preferences.update');
    Route::patch('settings/locale', LocaleController::class)->name('locale.update');
    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('settings/about', [AboutController::class, 'show'])->name('about.show');
    Route::get('settings/about/updates', [AboutController::class, 'updates'])
        ->middleware('throttle:30,1')->name('about.updates');
    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')->name('user-password.update');
});
