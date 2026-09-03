<?php

namespace App\Http\Requests\Settings;

use App\MembershipRole;
use App\WorkspaceIcon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePreferencesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->currentWorkspaceOrFail()->memberships()
            ->where('user_id', $user->id)
            ->where('role', MembershipRole::Owner)
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
            'icon' => ['required', Rule::enum(WorkspaceIcon::class)],
        ];
    }
}
