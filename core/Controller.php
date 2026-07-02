<?php

namespace Core;

abstract class Controller
{
    protected string $module = '';

    protected function view(string $view, array $data = [], string $layout = 'master'): void
    {
        extract($data);
        $viewFile = dirname(__DIR__) . '/views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View not found: {$view}");
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout) {
            $layoutFile = dirname(__DIR__) . '/views/layouts/' . $layout . '.php';
            if (!file_exists($layoutFile)) {
                throw new \RuntimeException("Layout not found: {$layout}");
            }
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    protected function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function redirect(string $path, array $flash = []): void
    {
        foreach ($flash as $key => $message) {
            Auth::flash($key, $message);
        }
        header('Location: ' . Auth::baseUrl($path));
        exit;
    }

    protected function requirePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method not allowed');
        }
    }

    protected function verifyCsrf(): void
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if ($token === '' && function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                if (strtolower($name) === 'x-csrf-token') {
                    $token = $value;
                    break;
                }
            }
        }
        if (!Auth::verifyCsrf($token)) {
            if (Auth::isAjax()) {
                $this->json(['success' => false, 'message' => 'Invalid CSRF token.'], 403);
            }
            Auth::flash('error', 'Invalid request. Please try again.');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? 'dashboard');
        }
    }

    protected function input(string $key, $default = null)
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function sanitize(string $value): string
    {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    protected function logActivity(string $action, string $module, ?int $recordId = null, ?string $details = null): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "INSERT INTO activity_log (tenant_id, user_id, user_type, action, module, record_id, details, ip_address, user_agent)
             VALUES (:tenant_id, :user_id, :user_type, :action, :module, :record_id, :details, :ip, :ua)"
        );
        $stmt->execute([
            'tenant_id' => Auth::tenantId(),
            'user_id'   => Auth::userId(),
            'user_type' => Auth::isSuperAdmin() ? 'super_admin' : 'user',
            'action'    => $action,
            'module'    => $module,
            'record_id' => $recordId,
            'details'   => $details,
            'ip'        => $_SERVER['REMOTE_ADDR'] ?? null,
            'ua'        => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
        ]);
    }

    protected function checkPermission(string $module, string $action): void
    {
        Auth::requirePermission($module, $action);
    }
}
