<?php

namespace Modules\Auth;

use Core\Auth;
use Core\Controller;
use Core\Helpers;

require_once __DIR__ . '/AuthViews.php';

class AuthController extends Controller
{
    private ?AuthModel $model = null;

    private function model(): AuthModel
    {
        if ($this->model === null) {
            require_once __DIR__ . '/AuthModel.php';
            $this->model = new AuthModel();
        }
        return $this->model;
    }

    public function login(): void
    {
        if (Auth::check()) {
            $this->redirect(Auth::isSuperAdmin() ? 'admin/dashboard' : 'dashboard');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();
            $email = trim($this->input('email', ''));
            $password = $this->input('password', '');
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

            if (empty($email) || empty($password)) {
                Auth::flash('error', 'Email and password are required.');
                $this->renderLoginView();
                return;
            }

            try {
                $attempts = $this->model()->getRecentAttempts($email, $ip, LOGIN_LOCKOUT_MINUTES);
                if ($attempts >= LOGIN_MAX_ATTEMPTS) {
                    Auth::flash('error', 'Too many login attempts. Try again in ' . LOGIN_LOCKOUT_MINUTES . ' minutes.');
                    $this->renderLoginView();
                    return;
                }

                $superAdmin = $this->model()->findSuperAdminByEmail($email);
                if ($superAdmin && password_verify($password, $superAdmin['password'])) {
                    $this->model()->clearLoginAttempts($email, $ip);
                    Auth::login($superAdmin, 'super_admin');
                    $this->logActivity('login', 'auth');
                    $this->redirect('admin/dashboard');
                }

                $user = $this->model()->findUserByEmail($email);
                if ($user && password_verify($password, $user['password'])) {
                    if ($user['status'] !== 'active') {
                        Auth::flash('error', 'Your account is inactive. Contact your shop owner.');
                        $this->renderLoginView();
                        return;
                    }
                    if ($user['shop_status'] === 'suspended') {
                        Auth::flash('error', 'Your account is suspended. Contact support.');
                        $this->renderLoginView();
                        return;
                    }
                    if ($user['shop_status'] === 'pending') {
                        Auth::flash('error', 'Your shop registration is pending approval.');
                        $this->renderLoginView();
                        return;
                    }

                    $this->model()->clearLoginAttempts($email, $ip);
                    $this->model()->updateLastLogin((int) $user['id']);
                    Auth::login($user, 'user');
                    $this->logActivity('login', 'auth', (int) $user['id']);
                    $this->redirect('dashboard');
                }

                $this->model()->recordLoginAttempt($email, $ip);
                Auth::flash('error', 'Invalid email or password.');
            } catch (\Throwable $e) {
                Auth::flash('error', 'Database not configured. Open /setup-database.php to connect MySQL.');
            }
        }

        $this->renderLoginView();
    }

    private function renderLoginView(): void
    {
        $root = dirname(__DIR__, 2);
        if (!AuthViews::viewsAreValid($root)) {
            AuthViews::repair($root);
        }

        $dbLocal = $root . '/storage/database.local.php';
        if (!is_file($dbLocal)) {
            $dbLocal = $root . '/config/database.local.php';
        }
        if (!is_file($dbLocal)) {
            Auth::flash('error', 'Database not configured. Open /setup-database.php first.');
        }

        if (AuthViews::viewsAreValid($root)) {
            $this->view('auth/login', ['pageTitle' => 'Login'], 'auth');
            return;
        }

        $this->renderLoginFallback();
    }

    private function renderLoginFallback(): void
    {
        $pageTitle = 'Login';
        $tmpDir = dirname(__DIR__, 2) . '/uploads/tmp';
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0755, true);
        }

        $loginFile = $tmpDir . '/_login_render.php';
        $layoutFile = $tmpDir . '/_auth_layout_render.php';
        file_put_contents($loginFile, AuthViews::loginTemplate());
        file_put_contents($layoutFile, AuthViews::layoutTemplate());

        ob_start();
        include $loginFile;
        $content = ob_get_clean() ?: '';

        include $layoutFile;
    }

    public function logout(): void
    {
        if (Auth::check()) {
            $this->logActivity('logout', 'auth');
        }
        Auth::logout();
        $this->redirect('auth/login');
    }

    public function register(): void
    {
        if (Auth::check()) {
            $this->redirect('dashboard');
        }
        $this->view('auth/register', ['pageTitle' => 'Register Your Shop'], 'auth');
    }

    public function registerSubmit(): void
    {
        $this->requirePost();
        $this->verifyCsrf();

        $data = [
            'shop_name'  => trim($this->input('shop_name', '')),
            'owner_name' => trim($this->input('owner_name', '')),
            'phone'      => trim($this->input('phone', '')),
            'city'       => trim($this->input('city', '')),
            'address'    => trim($this->input('address', '')),
            'shop_type'  => $this->input('shop_type', 'general'),
            'email'      => trim($this->input('email', '')),
            'password'   => $this->input('password', ''),
        ];

        $errors = [];
        if (empty($data['shop_name'])) $errors[] = 'Shop name is required.';
        if (empty($data['owner_name'])) $errors[] = 'Owner name is required.';
        if (empty($data['phone'])) $errors[] = 'Phone is required.';
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        if (strlen($data['password']) < 8) $errors[] = 'Password must be at least 8 characters.';

        $slug = Helpers::slugify($data['shop_name']);
        if ($this->model()->slugExists($slug)) {
            $slug .= '-' . random_int(100, 999);
        }
        $data['slug'] = $slug;

        if ($this->model()->emailExists($data['email'])) {
            $errors[] = 'Email is already registered.';
        }

        if ($errors) {
            $this->view('auth/register', [
                'pageTitle' => 'Register Your Shop',
                'errors'    => $errors,
                'old'       => $data,
            ], 'auth');
            return;
        }

        try {
            $shopId = $this->model()->registerShop($data);
            $this->logActivity('register', 'shops', $shopId, 'New shop registration: ' . $data['shop_name']);
            Auth::flash('success', 'Registration successful! Your shop is pending approval. You will be notified once approved.');
            $this->redirect('auth/login');
        } catch (\Exception $e) {
            Auth::flash('error', 'Registration failed. Please try again.');
            $this->redirect('register');
        }
    }
}
