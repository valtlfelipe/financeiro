<?php

namespace App\Http\Middleware;

use App\MembershipRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\Request;
use Inertia\Middleware;
use LogicException;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $this->user($request);
        $locale = $this->defaultLocale();

        if ($user !== null) {
            $locale = $user->locale;
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'locale' => $locale,
            'supportedLocales' => $this->supportedLocales(),
            'workspace' => fn (): ?array => $this->workspace($user),
            'workspaces' => fn (): array => $this->workspaces($user),
            'canCreateWorkspace' => fn (): bool => $user?->workspaces()
                ->wherePivot('role', MembershipRole::Owner->value)
                ->exists() ?? false,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /** @return array{id: int, name: string, icon: string, currency: string, timezone: string, role: string}|null */
    private function workspace(?User $user): ?array
    {
        $workspace = $user?->currentWorkspace;

        if ($workspace === null) {
            return null;
        }

        $role = $workspace->memberships()->where('user_id', $user->id)->value('role');

        return [
            'id' => $workspace->id,
            'name' => $workspace->name,
            'icon' => $workspace->icon,
            'currency' => $workspace->currency_code,
            'timezone' => $workspace->timezone,
            'role' => $this->roleValue($role),
        ];
    }

    /** @return list<array{id: int, name: string, icon: string, role: string}> */
    private function workspaces(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        $workspaces = $user->workspaces()
            ->orderBy('workspaces.name')
            ->orderBy('workspaces.id')
            ->get();
        $result = [];

        foreach ($workspaces as $workspace) {
            $pivot = $workspace->getRelationValue('pivot');
            $role = $pivot instanceof Pivot ? $pivot->getAttribute('role') : null;
            $result[] = [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'icon' => $workspace->icon,
                'role' => $this->roleValue($role),
            ];
        }

        return $result;
    }

    private function roleValue(mixed $role): string
    {
        if ($role instanceof MembershipRole) {
            return $role->value;
        }

        if (is_string($role)) {
            return $role;
        }

        throw new LogicException('The workspace membership role is missing.');
    }

    private function user(Request $request): ?User
    {
        $user = $request->user();

        return $user instanceof User ? $user : null;
    }

    private function defaultLocale(): string
    {
        $locale = config('locales.default');

        return is_string($locale) ? $locale : 'pt-BR';
    }

    /** @return list<array{code: string, name: string}> */
    private function supportedLocales(): array
    {
        $supported = config('locales.supported');

        if (! is_array($supported)) {
            return [];
        }

        $result = [];

        foreach ($supported as $code => $settings) {
            if (is_string($code) && is_array($settings) && is_string($settings['name'] ?? null)) {
                $result[] = ['code' => $code, 'name' => $settings['name']];
            }
        }

        return $result;
    }
}
