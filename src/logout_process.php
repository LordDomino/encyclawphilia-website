<?php
// logout_process.php (Controller Layer)

// Phase 1: Initialize session handling boundaries to access the current session state
session_start();

// Phase 2: Unset and clear all global session variables in the server memory heap
$_SESSION = [];

// Phase 3: Obliterate the client-side session identifier cookie if present
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000, // Force expiration timestamp deep into the past
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Phase 4: Destroy the backend session storage data entirely
session_destroy();

// Phase 5: Clear state indicators and redirect context back to the presentation layer
session_start(); // Briefly re-initialize a transient session to flash success metrics
$_SESSION['auth_error'] = [
    'type'  => 'success',
    'title' => 'Logged out',
    'body'  => 'You have been successfully logged out of your session.',
    'tab'   => 'login'
];

