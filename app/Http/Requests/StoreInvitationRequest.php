<?php

namespace App\Http\Requests;

use App\MembershipRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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

    /** @return array<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('email')) {
                return;
            }

            $workspace = $this->user()->currentWorkspaceOrFail();
            $email = $this->string('email')->lower()->toString();

            if ($workspace->users()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
                $validator->errors()->add('email', __('app.invitation.already_member'));

                return;
            }

            if ($workspace->invitations()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->whereNull('accepted_at')
                ->where('expires_at', '>', now())
                ->exists()) {
                $validator->errors()->add('email', __('app.invitation.already_pending'));
            }
        }];
    }
}
