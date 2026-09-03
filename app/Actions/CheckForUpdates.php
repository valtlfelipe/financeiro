<?php

namespace App\Actions;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CheckForUpdates
{
    private const string CACHE_KEY = 'financeiro.latest-release.v2';

    /** @return array{status: string, latestVersion: ?string, releaseUrl: ?string, checkedAt: string} */
    public function handle(bool $refresh = false): array
    {
        $currentVersion = $this->normalizeVersion(config('financeiro.version'));
        $cacheKey = self::CACHE_KEY.'.'.($currentVersion ?? 'development');

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        $release = Cache::get($cacheKey);

        if ($release === null) {
            $release = $this->fetchRelease();
            Cache::put($cacheKey, $release, $release['latestVersion'] === null ? 60 : 3600);
        }

        if ($release['latestVersion'] === null) {
            return $release;
        }

        return [
            'status' => $currentVersion === null
                ? 'development'
                : (version_compare($release['latestVersion'], $currentVersion, '>') ? 'available' : 'current'),
            'latestVersion' => $release['latestVersion'],
            'releaseUrl' => $release['releaseUrl'],
            'checkedAt' => $release['checkedAt'],
        ];
    }

    /** @return array{status: string, latestVersion: ?string, releaseUrl: ?string, checkedAt: string} */
    private function fetchRelease(): array
    {
        try {
            $response = Http::acceptJson()
                ->withUserAgent('Financeiro update checker')
                ->connectTimeout(3)
                ->timeout(8)
                ->get('https://api.github.com/repos/valtlfelipe/financeiro/releases/latest');

            $tag = $response->json('tag_name');
            $version = $this->normalizeVersion($tag);

            if ($response->successful()
                && $response->json('draft') === false
                && $response->json('prerelease') === false
                && $version !== null
                && ! str_contains($version, '-')) {
                return [
                    'status' => 'current',
                    'latestVersion' => $version,
                    'releaseUrl' => config('financeiro.repository').'/releases/tag/'.rawurlencode($tag),
                    'checkedAt' => now()->toIso8601String(),
                ];
            }
        } catch (ConnectionException) {
            // A self-hosted installation may not have internet access.
        }

        return [
            'status' => 'unavailable',
            'latestVersion' => null,
            'releaseUrl' => null,
            'checkedAt' => now()->toIso8601String(),
        ];
    }

    private function normalizeVersion(mixed $version): ?string
    {
        if (! is_string($version)
            || ! preg_match('/\Av?((?:0|[1-9]\d*)\.(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)(?:-[0-9A-Za-z.-]+)?)(?:\+[0-9A-Za-z.-]+)?\z/', $version, $matches)) {
            return null;
        }

        return $matches[1];
    }
}
