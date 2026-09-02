<?php

namespace App\Http\Requests;

use App\AccountType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->current_workspace_id !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::enum(AccountType::class)],
            'initial_balance_minor' => ['required', 'integer'],
            'balance_date' => ['required', 'date'],
            'icon' => ['nullable', 'string', 'max:40'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_archived' => ['sometimes', 'boolean'],
        ];
    }
}
