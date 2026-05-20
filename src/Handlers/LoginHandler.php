<?php

namespace App\Handlers;

use App\Models\UserModel;

class LoginHandler
{

    public static function handle()
    {
        // Initialize session handling boundaries
        session_start();

        // Assert the entry trajectory is strictly an HTTP POST method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: login');
            echo "<script>console.log('Exiting');</script>";
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
            header('Location: login');
            exit();
        }

        // Inject connection dependency to resolve data validation
        $pdo = \App\Controllers\DatabaseController::getDatabaseConnection();
        $user = new UserModel($pdo);
        $authOutcome = $user->verifyLogin($email, $password);

        if ($authOutcome['authenticated']) {
            // Prevent Session Fixation attacks by regenerating the identifier token
            session_regenerate_id(true);

            // Persist identity state metrics globally inside the server heap
            $_SESSION['user_id']   = $authOutcome['user']['id'];
            $_SESSION['username']  = $authOutcome['user']['username'];
            $_SESSION['role_id']   = $authOutcome['user']['role_id'];
            $sessionRoleId   = (int)($_SESSION['role_id'] ?? 1);
            $_SESSION['is_admin'] = ($sessionRoleId === 1);

            // Direct execution path to the secure application workspace
            header('Location: home');
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
            header('Location: login');
            exit();
        }
    }
}
