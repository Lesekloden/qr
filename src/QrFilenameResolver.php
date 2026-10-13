<?php

declare(strict_types=1);

namespace Lesekloden\Qr;

use Illuminate\Support\Str;

class QrFilenameResolver
{
    public function resolve(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '' || $path === '/') {
            return 'home';
        }

        $trimmedPath = trim($path, '/');
        $segments = array_filter(explode('/', $trimmedPath), static fn (string $segment): bool => $segment !== '');
        $flattenedPath = implode('-', $segments);
        $sanitizedPath = Str::of($flattenedPath)
            ->replaceMatches('/[^A-Za-z0-9]+/', '-')
            ->trim('-')
            ->lower()
            ->value();

        return $sanitizedPath !== '' ? $sanitizedPath : 'home';
    }
}
