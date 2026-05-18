<?php
// register_process.php (Controller Layer)

// Initialize session handling boundaries
session_start();

require_once '../config/database.php';
require_once 'procedures.php';

// Assert the entry trajectory is strictly an HTTP POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
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
    header('Location: ../login.php?tab=signup');
    exit();
}

if ($password !== $confirmPassword) {
    $_SESSION['auth_error'] = [
        'type'  => 'error',           // error | warning | info | success
        'title' => 'Signup failed',
        'body'  => 'Password confirmation parameters do not match.',
        'tab'   => 'signup'
    ];
    header('Location: ../login.php?tab=signup');
    exit();
}

if (!$termsAccepted) {
    $_SESSION['auth_error'] = [
        'type'  => 'error',           // error | warning | info | success
        'title' => 'Signup failed',
        'body'  => 'You must accept the Terms of Service to proceed.',
        'tab'   => 'signup'
    ];
    header('Location: ../login.php?tab=signup');
    exit();
}

try {
    // Inject connection dependency to resolve data validation
    $pdo = getDatabaseConnection();

    // Default role validation for Users
    $stmt = $pdo->query("SELECT role_id FROM roles WHERE role_name='Citizen';");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($results)) {
        throw new Exception("Target system role configuration 'Citizen' is missing.");
    }

    $roleId = (int)$results[0]['role_id'];

    // 2. Cryptographic Transformation Layer
    // Encrypt the raw string using a secure implementation algorithm (bcrypt/Argon2)
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // 3. Execution Phase: Invoke Procedure
    $authOutcome = registerUser($pdo, $username, $email, $passwordHash, $roleId);

    // 4. Evaluation Layer: Aligning with the procedure's actual return signature
    if (isset($authOutcome['user_id']) && $authOutcome['user_id'] > 0) {
        // Prevent Session Fixation attacks by regenerating the identifier token
        session_regenerate_id(true);

        // Persist identity state metrics globally inside the server heap
        $_SESSION['user_id']   = $authOutcome['user_id'];
        $_SESSION['full_name'] = $username;
        $_SESSION['role_id']   = $roleId;

        // Direct execution path to the secure application workspace
        header('Location: ../login.php');
        exit();
    } else {
        // Capture specific error responses thrown back by the procedure layer
        $_SESSION['auth_error'] = $authOutcome['message'] ?? "An unhandled exception occurred during registration.";
        header('Location: ../login.php');
        // In your login.php file
        if (isset($_SESSION['error'])) {
            echo '<p style="color:red">' . $_SESSION['error'] . '</p>';
            unset($_SESSION['error']); // Clear it so it doesn't show again
        }

        exit();
    }
} catch (Exception $e) {
    // Stage failure metrics for system anomalies and redirect safely
    $_SESSION['auth_error'] = "System Fault: " . $e->getMessage();
    header('Location: ../login.php');
    exit();
}
