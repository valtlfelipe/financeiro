<?php

namespace App\Http\Requests;

use App\MembershipRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreInvitationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user?->current_workspace_id === null) {
            return false;
        }

        return $user->workspaces()
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
            'email' => ['required', 'email', 'max:255'],
            'role' => ['sometimes', 'in:'.MembershipRole::Member->value],
        ];
    }
}
