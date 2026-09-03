<?php

namespace App\Http\Requests;

use App\MembershipRole;
use App\WorkspaceIcon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkspaceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->workspaces()
            ->wherePivot('role', MembershipRole::Owner->value)
            ->exists() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'workspace_name' => ['required', 'string', 'max:120'],
            'icon' => ['required', Rule::enum(WorkspaceIcon::class)],
        ];
    }
}
