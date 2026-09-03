<?php

namespace App\Http\Requests;

use App\MembershipRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteWorkspaceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->currentWorkspaceOrFail()->memberships()
            ->where('user_id', $user->id)
            ->where('role', MembershipRole::Owner->value)
            ->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $workspaceName = $this->user()?->currentWorkspaceOrFail()->name;

        return [
            'confirmation' => ['required', 'string', 'max:120', Rule::in([$workspaceName])],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'confirmation.in' => __('app.workspace.confirmation_mismatch'),
        ];
    }
}
