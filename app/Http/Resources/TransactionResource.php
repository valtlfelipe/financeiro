<?php

namespace App\Http\Resources;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Transaction */
class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'amountMinor' => (string) $this->amount_minor,
            'description' => $this->description,
            'dueOn' => $this->due_on->toDateString(),
            'settledAt' => $this->settled_at?->toIso8601String(),
            'notes' => $this->notes,
            'installmentNumber' => $this->installment_number,
            'installmentTotal' => $this->installment_total,
            'account' => $this->whenLoaded(
                'account',
                fn (): array => AccountResource::make($this->account)->resolve($request),
            ),
            'destinationAccount' => $this->whenLoaded('destinationAccount', fn () => $this->destinationAccount
                ? AccountResource::make($this->destinationAccount)->resolve($request)
                : null),
            'category' => $this->whenLoaded('category', fn () => $this->category
                ? CategoryResource::make($this->category)->resolve($request)
                : null),
            'series' => $this->whenLoaded('series', fn () => $this->series ? [
                'id' => $this->series->id,
                'kind' => $this->series->kind,
                'frequency' => $this->series->frequency,
            ] : null),
        ];
    }
}
