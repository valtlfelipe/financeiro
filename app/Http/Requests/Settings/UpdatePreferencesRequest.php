<?php

namespace App\Http\Requests\Settings;

use App\MembershipRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePreferencesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->workspaces()
            ->whereKey($user->current_workspace_id)
            ->wherePivot('role', MembershipRole::Owner->value)
            ->exists();
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
        ];
    }
}
