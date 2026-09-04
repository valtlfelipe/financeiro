<?php

namespace App\Http\Resources;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Account */
class AccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $balanceMinor = $this->resource->getAttribute('balance_minor');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'initialBalanceMinor' => (string) $this->initial_balance_minor,
            'balanceDate' => $this->balance_date->toDateString(),
            'balanceMinor' => $this->when($balanceMinor !== null, (string) $balanceMinor),
            'icon' => $this->icon,
            'color' => $this->color,
            'isArchived' => $this->is_archived,
        ];
    }
}
