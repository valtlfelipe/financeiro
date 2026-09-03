<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    public function actingAs(Authenticatable $user, $guard = null): static
    {
        parent::actingAs($user, $guard);

        $workspaceId = $user->getAuthIdentifier() !== null
            ? data_get($user, 'current_workspace_id')
            : null;

        if (is_int($workspaceId)) {
            $this->withHeader('X-Workspace-Id', (string) $workspaceId);
        }

        return $this;
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
