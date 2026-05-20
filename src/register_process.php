<?php

namespace App;
// register_process.php (Controller Layer)

// Initialize session handling boundaries
session_start();

use App\Models\UserModel;
use PDO;
use Exception;

// Assert the entry trajectory is strictly an HTTP POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login');
    exit();
}

// Ingest and isolate global input fields with fallback defaults
$username        = isset($_POST['username']) ? trim($_POST['username']) : '';
$email           = isset($_POST['email']) ? trim($_POST['email']) : '';
$password        = isset($_POST['password']) ? $_POST['password'] : '';
$confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
$termsAccepted   = isset($_POST['terms']);

// 1. Structural and Logical Validation Phase (Guard Clauses)
if ($username === '' || $email === '' || $password === '') {
    $_SESSION['auth_error'] = [
        'type'  => 'error',           // error | warning | info | success
        'title' => 'Signup failed',
        'body'  => 'All authentication fields are required.',
        'tab'   => 'signup'
    ];
    header('Location: login');
    exit();
}

if ($password !== $confirmPassword) {
    $_SESSION['auth_error'] = [
        'type'  => 'error',           // error | warning | info | success
        'title' => 'Signup failed',
        'body'  => 'Password confirmation parameters do not match.',
        'tab'   => 'signup'
    ];
    header('Location: login');
    exit();
}

if (!$termsAccepted) {
    $_SESSION['auth_error'] = [
        'type'  => 'error',           // error | warning | info | success
        'title' => 'Signup failed',
        'body'  => 'You must accept the Terms of Service to proceed.',
        'tab'   => 'signup'
    ];
    header('Location: login');
    exit();
}

try {
    // Inject connection dependency to resolve data validation
    $pdo = \App\Controllers\getDatabaseConnection();

    // Default role validation for Users
    $stmt = $pdo->query("SELECT role_id FROM Roles WHERE role_name='Citizen';");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($results)) {
        throw new Exception("Target system role configuration 'Citizen' is missing.");
    }

    $roleId = (int)$results[0]['role_id'];

    // 2. Cryptographic Transformation Layer
    // Encrypt the raw string using a secure implementation algorithm (bcrypt/Argon2)
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // 3. Execution Phase: Invoke Procedure
    $user = new UserModel($pdo);
    $authOutcome = $user->register($username, $email, $passwordHash, $roleId);

    // 4. Evaluation Layer: Aligning with the procedure's actual return signature
    if (isset($authOutcome['user_id']) && $authOutcome['user_id'] > 0) {
        // Prevent Session Fixation attacks by regenerating the identifier token
        session_regenerate_id(true);

        // Persist identity state metrics globally inside the server heap
        $_SESSION['user_id']   = $authOutcome['user_id'];
        $_SESSION['username'] = $username;
        $_SESSION['role_id']   = $roleId;

        $_SESSION['auth_error'] = [
            'type'  => 'success',           // error | warning | info | success
            'title' => 'Registration Successful',
            'body'  => 'You may now log in with your credentials.',
            'tab'   => 'login'
        ];

        require_once App\logout_process.php;

        // Direct execution path to the secure application workspace
        header('Location: login');
        exit();
    } else {
        // Capture specific error responses thrown back by the procedure layer
        $_SESSION['auth_error'] = [
            'type'  => 'error',           // error | warning | info | success
            'title' => 'Signup failed',
            'body'  => $authOutcome['message'] ?? "An unhandled exception occurred during registration.",
            'tab'   => 'signup'
        ];
        header('Location: login');
        // In your login.php file
        if (isset($_SESSION['error'])) {
            echo '<p style="color:red">' . $_SESSION['error'] . '</p>';
            unset($_SESSION['error']); // Clear it so it doesn't show again
        }

        exit();
    }
} catch (Exception $e) {
    // Stage failure metrics for system anomalies and redirect safely
    $_SESSION['auth_error'] = [
        'type'  => 'error',           // error | warning | info | success
        'title' => 'Signup failed',
        'body'  => 'An unexpected error occured: ' . $e->getMessage(),
        'tab'   => 'signup'
    ];
    header('Location: login');
    exit();
}
