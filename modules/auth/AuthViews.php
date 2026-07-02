<?php

namespace Modules\Auth;

class AuthViews
{
    public static function isStaleLogin(string $content): bool
    {
        if ($content === '') {
            return true;
        }

        $markers = ['assetUrl(', 'App\\Core\\Session', 'App\\Core\\', 'PakPOS'];
        foreach ($markers as $marker) {
            if (str_contains($content, $marker)) {
                return true;
            }
        }

        return !str_contains($content, '\\Core\\Auth::csrfField()');
    }

    public static function isStaleLayout(string $content): bool
    {
        if ($content === '') {
            return true;
        }

        return str_contains($content, 'font-awesome')
            || !str_contains($content, 'bootstrap-icons');
    }

    public static function loginTemplate(): string
    {
        return <<<'HTML'
<form method="POST" action="<?= \Core\Auth::baseUrl('auth/login') ?>">
    <?= \Core\Auth::csrfField() ?>
    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required autofocus
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary w-100">Login</button>
    <div class="text-center mt-3">
        <a href="<?= \Core\Auth::baseUrl('register') ?>">Register your shop</a>
    </div>
</form>
HTML;
    }

    public static function layoutTemplate(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Login - ' . APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= \Core\Auth::asset('css/app.css') ?>" rel="stylesheet">
</head>
<body class="auth-body">
    <div class="auth-wrapper d-flex align-items-center justify-content-center min-vh-100">
        <div class="auth-card card shadow-lg border-0" style="width:100%;max-width:420px;">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <i class="bi bi-shop display-4 text-primary"></i>
                    <h4 class="mt-2"><?= htmlspecialchars(APP_NAME) ?></h4>
                </div>
                <?php if (!empty($error = \Core\Auth::flash('error'))): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if (!empty($success = \Core\Auth::flash('success'))): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <?= $content ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
HTML;
    }

    /** @return array{login: bool, layout: bool} */
    public static function repair(string $root): array
    {
        $loginDir = $root . '/views/auth';
        $layoutDir = $root . '/views/layouts';

        if (!is_dir($loginDir)) {
            @mkdir($loginDir, 0755, true);
        }
        if (!is_dir($layoutDir)) {
            @mkdir($layoutDir, 0755, true);
        }

        $loginPath = $loginDir . '/login.php';
        $layoutPath = $layoutDir . '/auth.php';

        return [
            'login'  => file_put_contents($loginPath, self::loginTemplate()) !== false,
            'layout' => file_put_contents($layoutPath, self::layoutTemplate()) !== false,
        ];
    }

    public static function viewsAreValid(string $root): bool
    {
        $loginPath = $root . '/views/auth/login.php';
        $layoutPath = $root . '/views/layouts/auth.php';

        if (!is_file($loginPath) || !is_file($layoutPath)) {
            return false;
        }

        return !self::isStaleLogin((string) file_get_contents($loginPath))
            && !self::isStaleLayout((string) file_get_contents($layoutPath));
    }
}
