<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Services\AuthService;

class AuthController
{
    private function makeAuthService(): AuthService
    {
        $pdo = DatabaseController::getDatabaseConnection();
        return new AuthService(new UserModel($pdo));
    }

    // ── POST /login-submit ────────────────────────────────────────────────
    public function handleLoginSubmit(): void
    {
        session_start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /login');
            exit;
        }

        $email    = trim($_POST['email']    ?? '');
        $password =      $_POST['password'] ?? '';

        $result = $this->makeAuthService()->login($email, $password);

        if ($result['ok']) {
            session_regenerate_id(true);
            $user = $result['user'];

            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_id']  = $user['role_id'];
            $_SESSION['is_admin'] = ($user['role_id'] === 1);

            header('Location: /home');
            exit;
        }

        $this->flashError($result['message'], 'Login failed', 'login');
        header('Location: /login');
        exit;
    }

    // ── POST /signup-submit ───────────────────────────────────────────────
    public function handleSignupSubmit(): void
    {
        session_start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /login');
            exit;
        }

        $pdo    = DatabaseController::getDatabaseConnection();
        $roleId = $this->resolveCitizenRoleId($pdo);

        if ($roleId === null) {
            $this->flashError("System role 'Citizen' is not configured.", 'Signup failed', 'signup');
            header('Location: /login');
            exit;
        }

        $result = $this->makeAuthService()->register(
            username:        trim($_POST['username']         ?? ''),
            email:           trim($_POST['email']            ?? ''),
            password:             $_POST['password']         ?? '',
            confirmPassword:      $_POST['confirm_password'] ?? '',
            termsAccepted:   isset($_POST['terms']),
            roleId:          $roleId
        );

        if ($result['ok']) {
            $_SESSION['auth_error'] = [
                'type'  => 'success',
                'title' => 'Registration successful',
                'body'  => 'You may now log in with your credentials.',
                'tab'   => 'login',
            ];
            header('Location: /login');
            exit;
        }

        $this->flashError($result['message'], 'Signup failed', 'signup');
        header('Location: /login');
        exit;
    }

    // ── GET /logout-submit ────────────────────────────────────────────────
    public function handleLogoutSubmit(): void
    {
        session_start();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }

        session_destroy();
        session_start();

        $_SESSION['auth_error'] = [
            'type'  => 'success',
            'title' => 'Logged out',
            'body'  => 'You have been successfully logged out.',
            'tab'   => 'login',
        ];

        header('Location: /login');
        exit;
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function flashError(string $body, string $title, string $tab): void
    {
        $_SESSION['auth_error'] = [
            'type'  => 'error',
            'title' => $title,
            'body'  => $body,
            'tab'   => $tab,
        ];
    }

    private function resolveCitizenRoleId(\PDO $pdo): ?int
    {
        $stmt = $pdo->query("SELECT role_id FROM Roles WHERE role_name = 'Citizen' LIMIT 1");
        $row  = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? (int) $row['role_id'] : null;
    }
}