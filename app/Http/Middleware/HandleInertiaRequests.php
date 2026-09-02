<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

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
        $workspace = null;
        $locale = $this->defaultLocale();

        if ($user !== null) {
            $workspace = $user->currentWorkspace;
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
            'workspace' => $workspace ? [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'currency' => $workspace->currency_code,
                'timezone' => $workspace->timezone,
                'role' => $workspace->memberships()
                    ->where('user_id', $user->id)
                    ->value('role'),
            ] : null,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
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
