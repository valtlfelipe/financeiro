<?php

namespace App\Http\Requests;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class AcceptInvitationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->invitation() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $invitation = $this->invitation();
        $existing = $invitation !== null
            && User::query()->where('email', $invitation->email)->exists();

        return [
            'name' => [$existing ? 'nullable' : 'required', 'string', 'max:120'],
            'password' => ['required', $existing ? 'string' : 'confirmed', Password::defaults()],
        ];
    }

    public function invitation(): ?Invitation
    {
        return Invitation::query()
            ->where('token_hash', hash('sha256', (string) $this->route('token')))
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();
    }
}
