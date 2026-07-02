<?php

declare(strict_types=1);

/**
 * Global helpers for legacy PakPOS view templates still on some servers.
 */

if (!function_exists('assetUrl')) {
    function assetUrl(string $path = ''): string
    {
        return \Core\Auth::asset($path);
    }
}

if (!function_exists('baseUrl')) {
    function baseUrl(string $path = ''): string
    {
        return \Core\Auth::baseUrl($path);
    }
}
