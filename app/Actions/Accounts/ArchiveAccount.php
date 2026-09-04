<?php

namespace App\Actions\Accounts;

use App\Actions\Transactions\AccountBalance;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\TransactionSeries;
use App\SeriesKind;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ArchiveAccount
{
    public function __construct(private readonly AccountBalance $accountBalance) {}

    public function handle(Account $account): void
    {
        DB::transaction(function () use ($account): void {
            $account = Account::query()->with('workspace')->lockForUpdate()->findOrFail($account->id);
            $activeAccountIds = Account::query()
                ->where('workspace_id', $account->workspace_id)
                ->where('is_archived', false)
                ->lockForUpdate()
                ->pluck('id');

            if ($activeAccountIds->count() <= 1) {
                $this->fail('app.account.cannot_archive_last');
            }

            if ($this->accountBalance->handle($account) !== '0') {
                $this->fail('app.account.cannot_archive_balance');
            }

            $movements = Transaction::query()
                ->where('workspace_id', $account->workspace_id)
                ->where(fn ($query) => $query
                    ->where('account_id', $account->id)
                    ->orWhere('destination_account_id', $account->id));

            if ((clone $movements)->whereNull('settled_at')->exists()) {
                $this->fail('app.account.cannot_archive_pending');
            }

            if ((clone $movements)->whereDate('due_on', '>', $account->workspace->today()->toDateString())->exists()) {
                $this->fail('app.account.cannot_archive_future');
            }

            $hasActiveRecurrence = TransactionSeries::query()
                ->where('workspace_id', $account->workspace_id)
                ->where('kind', SeriesKind::Recurring)
                ->where(fn ($query) => $query
                    ->where('account_id', $account->id)
                    ->orWhere('destination_account_id', $account->id))
                ->where(fn ($query) => $query
                    ->whereNull('ends_on')
                    ->orWhereDate('ends_on', '>=', $account->workspace->today()->toDateString()))
                ->exists();

            if ($hasActiveRecurrence) {
                $this->fail('app.account.cannot_archive_recurring');
            }

            $account->update(['is_archived' => true]);
        });
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages([
            'account' => __($message),
        ]);
    }
}
