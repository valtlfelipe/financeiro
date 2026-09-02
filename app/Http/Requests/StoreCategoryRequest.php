<?php

namespace App\Http\Requests;

use App\CategoryType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('categories')->where(fn ($query) => $query
                    ->where('workspace_id', $this->user()?->current_workspace_id)
                    ->where('type', $this->string('type')))
                    ->ignore($this->route('category')),
            ],
            'type' => ['required', Rule::enum(CategoryType::class)],
            'icon' => ['nullable', 'string', 'max:40'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_archived' => ['sometimes', 'boolean'],
        ];
    }
}
