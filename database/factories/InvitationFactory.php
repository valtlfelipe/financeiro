<?php

namespace Database\Factories;

use App\MembershipRole;
use App\Models\Invitation;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invitation>
 */
class InvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'invited_by' => User::factory(),
            'email' => fake()->unique()->safeEmail(),
            'role' => MembershipRole::Member,
            'token_hash' => hash('sha256', Str::random(64)),
            'expires_at' => now()->addDays(7),
        ];
    }
}
