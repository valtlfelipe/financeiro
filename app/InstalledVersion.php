<?php

namespace App;

final class InstalledVersion
{
    public static function detect(string $releaseFile, mixed $fallback = 'dev'): string
    {
        $releaseVersion = is_file($releaseFile)
            ? self::validVersion(file_get_contents($releaseFile))
            : null;

        return $releaseVersion ?? self::validVersion($fallback) ?? 'dev';
    }

    private static function validVersion(mixed $version): ?string
    {
        if (! is_string($version)) {
            return null;
        }

        $version = trim($version);

        if ($version === 'dev') {
            return $version;
        }

        return preg_match('/\Av?(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)(?:-[0-9A-Za-z.-]+)?(?:\+[0-9A-Za-z.-]+)?\z/', $version) === 1
            ? $version
            : null;
    }
}
