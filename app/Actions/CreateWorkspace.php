<?php

namespace App\Actions;

use App\AccountType;
use App\CategoryType;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class CreateWorkspace
{
    public function handle(User $owner, string $name): Workspace
    {
        return DB::transaction(function () use ($owner, $name): Workspace {
            $workspace = Workspace::query()->create([
                'name' => $name,
                'currency_code' => 'BRL',
                'timezone' => 'America/Sao_Paulo',
            ]);
            $workspace->addOwner($owner);

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
            ] as [$categoryName, $type, $icon, $color]) {
                $workspace->categories()->create([
                    'name' => $categoryName,
                    'type' => $type,
                    'icon' => $icon,
                    'color' => $color,
                ]);
            }

            return $workspace;
        });
    }
}
