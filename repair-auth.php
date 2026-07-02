<?php

declare(strict_types=1);

/**
 * ONE-TIME repair for stale PakPOS auth views — delete after use.
 * Visit: https://pos.varitaxes.com/repair-auth.php
 */

header('Content-Type: text/plain; charset=utf-8');

$root = __DIR__;

require_once $root . '/vendor/autoload.php';
require_once $root . '/modules/auth/AuthViews.php';

$loginBefore = is_file($root . '/views/auth/login.php')
    ? (string) file_get_contents($root . '/views/auth/login.php')
    : '';
$layoutBefore = is_file($root . '/views/layouts/auth.php')
    ? (string) file_get_contents($root . '/views/layouts/auth.php')
    : '';

echo "=== Auth view repair ===\n";
echo 'login stale: ' . (\Modules\Auth\AuthViews::isStaleLogin($loginBefore) ? 'yes' : 'no') . "\n";
echo 'layout stale: ' . (\Modules\Auth\AuthViews::isStaleLayout($layoutBefore) ? 'yes' : 'no') . "\n\n";

$result = \Modules\Auth\AuthViews::repair($root);

echo ($result['login'] ? 'wrote' : 'FAILED') . ": views/auth/login.php\n";
echo ($result['layout'] ? 'wrote' : 'FAILED') . ": views/layouts/auth.php\n";

if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "opcache: reset\n";
}

echo "\nDone. Test: /auth/login\n";
echo "Delete repair-auth.php when finished.\n";
