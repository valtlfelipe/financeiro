<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class UpdateTransactionRequest extends StoreTransactionRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'scope' => ['sometimes', 'in:single,future'],
        ];
    }
}
