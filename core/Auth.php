<?php

namespace Core;

class Auth
{
    private static ?array $permissionsCache = null;

    public static function init(): void
    {
        $appConfig = require dirname(__DIR__) . '/config/app.php';
        if (session_status() === PHP_SESSION_NONE) {
            session_name($appConfig['session_name']);
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'use_strict_mode' => true,
            ]);
        }
    }

    public static function login(array $user, string $type = 'user'): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = $user;
        $_SESSION['user_type'] = $type;
        $_SESSION['logged_in'] = true;
        self::$permissionsCache = null;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        self::$permissionsCache = null;
    }

    public static function check(): bool
    {
        return !empty($_SESSION['logged_in']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function userType(): ?string
    {
        return $_SESSION['user_type'] ?? null;
    }

    public static function isSuperAdmin(): bool
    {
        return self::userType() === 'super_admin';
    }

    public static function role(): ?string
    {
        if (self::isSuperAdmin()) {
            return 'super_admin';
        }
        return self::user()['role'] ?? null;
    }

    public static function tenantId(): ?int
    {
        if (self::isSuperAdmin() && !empty($_SESSION['impersonate_tenant_id'])) {
            return (int) $_SESSION['impersonate_tenant_id'];
        }
        if (self::isSuperAdmin()) {
            return null;
        }
        return isset(self::user()['tenant_id']) ? (int) self::user()['tenant_id'] : null;
    }

    public static function userId(): ?int
    {
        return isset(self::user()['id']) ? (int) self::user()['id'] : null;
    }

    public static function isImpersonating(): bool
    {
        return self::isSuperAdmin() && !empty($_SESSION['impersonate_tenant_id']);
    }

    public static function impersonate(int $tenantId): void
    {
        $_SESSION['impersonate_tenant_id'] = $tenantId;
        $_SESSION['impersonate_readonly'] = true;
        self::$permissionsCache = null;
    }

    public static function stopImpersonating(): void
    {
        unset($_SESSION['impersonate_tenant_id'], $_SESSION['impersonate_readonly']);
        self::$permissionsCache = null;
    }

    public static function isReadOnly(): bool
    {
        return !empty($_SESSION['impersonate_readonly']);
    }

    public static function can(string $module, string $action): bool
    {
        if (self::isReadOnly() && !in_array($action, ['read', 'print'], true)) {
            return false;
        }

        $role = self::role();
        if (!$role) {
            return false;
        }

        $perms = self::getPermissions($role);
        return in_array("{$module}.{$action}", $perms, true);
    }

    public static function requirePermission(string $module, string $action): void
    {
        if (!self::can($module, $action)) {
            http_response_code(403);
            if (self::isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
                exit;
            }
            self::flash('error', 'You do not have permission to perform this action.');
            header('Location: ' . self::baseUrl('dashboard'));
            exit;
        }
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: ' . self::baseUrl('auth/login'));
            exit;
        }
    }

    public static function requireSuperAdmin(): void
    {
        self::requireLogin();
        if (!self::isSuperAdmin() || self::isImpersonating()) {
            http_response_code(403);
            self::flash('error', 'Super admin access required.');
            header('Location: ' . self::baseUrl('dashboard'));
            exit;
        }
    }

    public static function checkShopStatus(): void
    {
        if (self::isSuperAdmin() && !self::isImpersonating()) {
            return;
        }

        $tenantId = self::tenantId();
        if (!$tenantId) {
            return;
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT status FROM shops WHERE id = :id AND is_deleted = 0");
        $stmt->execute(['id' => $tenantId]);
        $shop = $stmt->fetch();

        if (!$shop || $shop['status'] === 'suspended') {
            self::logout();
            self::flash('error', 'Your account is suspended. Contact support.');
            header('Location: ' . self::baseUrl('auth/login'));
            exit;
        }

        if ($shop['status'] === 'pending') {
            self::logout();
            self::flash('error', 'Your shop registration is pending approval.');
            header('Location: ' . self::baseUrl('auth/login'));
            exit;
        }
    }

    private static function getPermissions(string $role): array
    {
        if (self::$permissionsCache !== null) {
            return self::$permissionsCache;
        }

        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT module, action FROM roles_permissions 
             WHERE role = :role AND is_deleted = 0"
        );
        $stmt->execute(['role' => $role]);
        $rows = $stmt->fetchAll();

        self::$permissionsCache = array_map(
            fn($r) => $r['module'] . '.' . $r['action'],
            $rows
        );

        return self::$permissionsCache;
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::csrfToken()) . '">';
    }

    public static function verifyCsrf(?string $token): bool
    {
        return $token && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function flash(string $key, ?string $message = null): ?string
    {
        if ($message !== null) {
            $_SESSION['flash'][$key] = $message;
            return null;
        }
        $msg = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $msg;
    }

    public static function baseUrl(string $path = ''): string
    {
        $appConfig = require dirname(__DIR__) . '/config/app.php';
        $base = rtrim($appConfig['base_url'], '/');
        return $path ? $base . '/' . ltrim($path, '/') : $base;
    }

    public static function asset(string $path): string
    {
        return self::baseUrl('assets/' . ltrim($path, '/'));
    }

    public static function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    public static function canSeePurchasePrice(): bool
    {
        return in_array(self::role(), ['super_admin', 'owner', 'manager'], true);
    }
}
