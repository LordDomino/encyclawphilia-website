<?php
// login_process.php (Controller Layer)

// Initialize session handling boundaries
session_start();

require_once '../config/database.php';
require_once 'procedures.php';

// Assert the entry trajectory is strictly an HTTP POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

// Ingest and isolate global input fields
$email    = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if ($email === '' || $password === '') {
    $_SESSION['auth_error'] = [
        'type'  => 'error',           // error | warning | info | success
        'title' => 'Login failed',
        'body'  => 'All authentication fields are required.',
        'tab'   => 'login'
    ];
    header('Location: login.php');
    exit();
}

// Inject connection dependency to resolve data validation
$pdo = getDatabaseConnection();
$authOutcome = verifyUserLogin($pdo, $email, $password);

if ($authOutcome['authenticated']) {
    // Prevent Session Fixation attacks by regenerating the identifier token
    session_regenerate_id(true);

    // Persist identity state metrics globally inside the server heap
    $_SESSION['user_id']   = $authOutcome['user']['id'];
    $_SESSION['username']  = $authOutcome['user']['username'];
    $_SESSION['role_id']   = $authOutcome['user']['role_id'];

    // Direct execution path to the secure application workspace
    header('Location: ../index.php');
    exit();
} else {
    // Stage failure metrics and return execution focus back to presentation layout
    // On failure:
    $_SESSION['auth_error'] = [
        'type'  => 'error',           // error | warning | info | success
        'title' => 'Login failed',
        'body'  => 'Incorrect email or password. Please try again.',
        'tab'   => 'login'
    ];
    header('Location: ../login.php');
    exit();
}
