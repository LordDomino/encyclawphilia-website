<?php

namespace App\Controllers;

class AuthController
{
    public function handleLoginSubmit(): void
    {
        // Process active POST request data securely
        // require_once __DIR__ . '/../login_process.php';
        \App\Handlers\LoginHandler::handle();
    }

    // The variable name matching the route token is fed in dynamically
    public function showProfile(string $id): void
    {
        echo "Securing filesystem access... Fetching context for user ID: " . htmlspecialchars($id);
        // Perform database operations: SELECT * FROM users WHERE id = :id
    }

    public function handleLogoutSubmit(): void
    {
        require_once __DIR__ . '/../logout_process.php';
        header('Location: home');
        $_SESSION['is_admin'] = false;
        exit();
    }

    public function handleSignupSubmit(): void
    {
        require_once __DIR__ . '/../register_process.php';
    }
}
