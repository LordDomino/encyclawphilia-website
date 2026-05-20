<?php

namespace App\Services;

use App\Models\UserModel;

class AuthService
{
    public function __construct(private readonly UserModel $userModel) {}

    /**
     * Validates credentials and returns the authenticated user's identity,
     * or an error message if authentication fails.
     *
     * @return array{ ok: bool, message: string, user?: array }
     */
    public function login(string $email, string $password): array
    {
        $email    = strtolower(trim($email));
        $password = trim($password);

        if ($email === '' || $password === '') {
            return ['ok' => false, 'message' => 'All authentication fields are required.'];
        }

        $outcome = $this->userModel->verifyLogin($email, $password);

        if (!$outcome['authenticated']) {
            return ['ok' => false, 'message' => 'Incorrect email or password. Please try again.'];
        }

        return ['ok' => true, 'user' => $outcome['user']];
    }

    /**
     * Validates and persists a new user account.
     *
     * @return array{ ok: bool, message: string, user_id?: int }
     */
    public function register(
        string $username,
        string $email,
        string $password,
        string $confirmPassword,
        bool   $termsAccepted,
        int    $roleId
    ): array {
        if ($username === '' || $email === '' || $password === '') {
            return ['ok' => false, 'message' => 'All authentication fields are required.'];
        }

        if ($password !== $confirmPassword) {
            return ['ok' => false, 'message' => 'Password confirmation does not match.'];
        }

        if (!$termsAccepted) {
            return ['ok' => false, 'message' => 'You must accept the Terms of Service to proceed.'];
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $outcome      = $this->userModel->register($username, $email, $passwordHash, $roleId);

        if ($outcome['user_id'] < 1) {
            return ['ok' => false, 'message' => $outcome['message']];
        }

        return ['ok' => true, 'user_id' => $outcome['user_id']];
    }
}