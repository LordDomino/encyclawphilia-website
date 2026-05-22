<?php

namespace App\Controllers;

require_once __DIR__ . '/../Core/ApiResponse.php';

use App\Core\ApiResponse;
use App\Models\OrdinanceModel;
use App\Models\UserModel;
use PDOException;

class UserController
{
    public function handleOrdinanceReact(): void
    {
        session_start();

        // Guard: authentication
        if (empty($_SESSION['user_id'])) {
            ApiResponse::send(ApiResponse::error('You must be logged in to react.', 401), 401);
        }

        // Guard: method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiResponse::send(ApiResponse::error('Method not allowed.', 405), 405);
        }

        // Input validation and casting
        $ordinanceId  = filter_input(INPUT_POST, 'ordinance_id',  FILTER_VALIDATE_INT);
        $reactionType = filter_input(INPUT_POST, 'reaction_type', FILTER_SANITIZE_SPECIAL_CHARS);
        $userId       = (int) $_SESSION['user_id'];

        if ($ordinanceId === false || $ordinanceId === null || $ordinanceId < 1) {
            ApiResponse::send(ApiResponse::error('Invalid ordinance_id.', 422), 422);
        }

        if (!in_array($reactionType, ['like', 'dislike'], strict: true)) {
            ApiResponse::send(ApiResponse::error('reaction_type must be "like" or "dislike".', 422), 422);
        }

        // Delegate to repository
        try {
            $pdo        = \App\Controllers\DatabaseController::getDatabaseConnection();
            $repository = new OrdinanceModel($pdo);

            $result = $repository->toggleReaction(
                ordinanceId: $ordinanceId,
                userId: $userId,
                reactionType: $reactionType
            );
        } catch (PDOException $e) {
            error_log('toggleReaction failure: ' . $e->getMessage());
            ApiResponse::send(
                ApiResponse::error('A database error occurred. Please try again.', 500),
                500
            );
        }

        // ── Wrap and send ────────────────────────────────────────────────
        ApiResponse::send(
            ApiResponse::success(
                data: [
                    'action'       => $result['action'],       // 'added'|'removed'|'switched'
                    'userReaction' => $result['userReaction'], // 'like'|'dislike'|null
                    'likes'        => $result['likes'],
                    'dislikes'     => $result['dislikes'],
                ],
                message: match ($result['action']) {
                    'added'    => 'Reaction recorded.',
                    'removed'  => 'Reaction removed.',
                    'switched' => 'Reaction updated.',
                }
            ),
            httpStatus: 200
        );
    }

    public function handleOrdinanceComment(): void
    {
        session_start();

        if (empty($_SESSION['user_id'])) {
            ApiResponse::send(ApiResponse::error('You must be logged in to comment.', 401), 401);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiResponse::send(ApiResponse::error('Method not allowed.', 405), 405);
        }

        $ordinanceId  = filter_input(INPUT_POST, 'ordinance_id', FILTER_VALIDATE_INT);
        $commentText  = trim($_POST['comment_text'] ?? '');
        $userId       = (int) $_SESSION['user_id'];
        $username     = $_SESSION['username'] ?? 'User';

        if ($ordinanceId === false || $ordinanceId === null || $ordinanceId < 1) {
            ApiResponse::send(ApiResponse::error('Invalid ordinance_id.', 422), 422);
        }

        if ($commentText === '') {
            ApiResponse::send(ApiResponse::error('Comment cannot be empty.', 422), 422);
        }

        if (mb_strlen($commentText) > 1000) {
            ApiResponse::send(ApiResponse::error('Comment must be 1000 characters or fewer.', 422), 422);
        }

        try {
            $pdo  = DatabaseController::getDatabaseConnection();

            // Verify ordinance exists and is not archived
            $check = $pdo->prepare('SELECT ordinance_id FROM Ordinances WHERE ordinance_id = :id AND archived_at IS NULL LIMIT 1');
            $check->execute([':id' => $ordinanceId]);
            if ($check->fetch() === false) {
                ApiResponse::send(ApiResponse::error('Ordinance not found or is no longer active.', 404), 404);
            }

            $stmt = $pdo->prepare('
            INSERT INTO Comments (ordinance_id, user_id, comment_text)
            VALUES (:ordinance_id, :user_id, :comment_text)
        ');
            $stmt->execute([
                ':ordinance_id' => $ordinanceId,
                ':user_id'      => $userId,
                ':comment_text' => $commentText,
            ]);

            $commentId = (int) $pdo->lastInsertId();
        } catch (\PDOException $e) {
            error_log('handleOrdinanceComment failure: ' . $e->getMessage());
            ApiResponse::send(ApiResponse::error('A database error occurred. Please try again.', 500), 500);
        }

        ApiResponse::send(
            ApiResponse::success(
                data: [
                    'comment_id'   => $commentId,
                    'ordinance_id' => $ordinanceId,
                    'user_id'      => $userId,
                    'username'     => htmlspecialchars($username, ENT_QUOTES, 'UTF-8'),
                    'comment_text' => htmlspecialchars($commentText, ENT_QUOTES, 'UTF-8'),
                    'created_at'   => date('Y-m-d H:i:s'),
                ],
                message: 'Comment posted.'
            ),
            200
        );
    }

    public function handleCommentReact(): void
    {
        session_start();

        if (empty($_SESSION['user_id'])) {
            ApiResponse::send(ApiResponse::error('You must be logged in to react.', 401), 401);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiResponse::send(ApiResponse::error('Method not allowed.', 405), 405);
        }

        $commentId    = filter_input(INPUT_POST, 'comment_id',    FILTER_VALIDATE_INT);
        $reactionType = filter_input(INPUT_POST, 'reaction_type', FILTER_SANITIZE_SPECIAL_CHARS);
        $userId       = (int) $_SESSION['user_id'];

        if ($commentId === false || $commentId === null || $commentId < 1) {
            ApiResponse::send(ApiResponse::error('Invalid comment_id.', 422), 422);
        }

        if (!in_array($reactionType, ['like', 'dislike'], strict: true)) {
            ApiResponse::send(ApiResponse::error('reaction_type must be "like" or "dislike".', 422), 422);
        }

        try {
            $pdo        = DatabaseController::getDatabaseConnection();
            $repository = new \App\Models\CommentModel($pdo);

            $result = $repository->toggleReaction(
                commentId: $commentId,
                userId: $userId,
                reactionType: $reactionType
            );
        } catch (\PDOException $e) {
            error_log('handleCommentReact failure: ' . $e->getMessage());
            ApiResponse::send(
                ApiResponse::error('A database error occurred. Please try again.', 500),
                500
            );
        }

        ApiResponse::send(
            ApiResponse::success(
                data: [
                    'action'       => $result['action'],
                    'userReaction' => $result['userReaction'],
                    'likes'        => $result['likes'],
                    'dislikes'     => $result['dislikes'],
                ],
                message: match ($result['action']) {
                    'added'    => 'Reaction recorded.',
                    'removed'  => 'Reaction removed.',
                    'switched' => 'Reaction updated.',
                }
            ),
            httpStatus: 200
        );
    }

    public function updateUsername(): void
    {
        session_start();

        if (empty($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /account');
            exit;
        }

        $newUsername = trim($_POST['username'] ?? '');
        $userId      = (int) $_SESSION['user_id'];

        if ($newUsername === '') {
            $this->redirectWithFlash('error', 'Username update failed.', 'Please enter a new username.');
        }

        if (mb_strlen($newUsername) < 3 || mb_strlen($newUsername) > 100) {
            $this->redirectWithFlash('error', 'Invalid username.', 'Username must be 3–100 characters long.');
        }

        if ($newUsername === ($_SESSION['username'] ?? '')) {
            $this->redirectWithFlash('info', 'No changes made.', 'The new username matches your current username.');
        }

        $result = ['ok' => false, 'message' => ''];
        try {
            $pdo        = DatabaseController::getDatabaseConnection();
            $repository = new UserModel($pdo);

            $result = $repository->updateUsername($userId, $newUsername);
        } catch (PDOException $e) {
            error_log('updateUsername failure: ' . $e->getMessage());
            $this->redirectWithFlash('error', 'Update failed.', 'A database error occurred. Please try again.');
        }

        if (!$result['ok']) {
            $this->redirectWithFlash('error', 'Update failed.', $result['message']);
        }

        $_SESSION['username'] = $newUsername;
        $_SESSION['account_flash'] = [
            'type'  => 'success',
            'title' => 'Username updated',
            'body'  => 'Your display name has been updated successfully.',
        ];

        header('Location: /account');
        exit;
    }

    public function updatePassword(): void
    {
        session_start();

        if (empty($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /account');
            exit;
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword     = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_new_password'] ?? '';
        $userId          = (int) $_SESSION['user_id'];

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $this->redirectWithFlash('error', 'Password update failed.', 'All password fields are required.');
        }

        if ($newPassword !== $confirmPassword) {
            $this->redirectWithFlash('error', 'Password mismatch.', 'The new passwords do not match.');
        }

        if (mb_strlen($newPassword) < 8) {
            $this->redirectWithFlash('error', 'Password too short.', 'New password must be at least 8 characters long.');
        }

        $result = ['ok' => false, 'message' => ''];
        try {
            $pdo        = DatabaseController::getDatabaseConnection();
            $repository = new UserModel($pdo);

            if (!$repository->verifyPasswordById($userId, $currentPassword)) {
                $this->redirectWithFlash('error', 'Incorrect password.', 'Your current password is invalid.');
            }

            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $result       = $repository->updatePassword($userId, $passwordHash);
        } catch (PDOException $e) {
            error_log('updatePassword failure: ' . $e->getMessage());
            $this->redirectWithFlash('error', 'Update failed.', 'A database error occurred. Please try again.');
        }

        if (!$result['ok']) {
            $this->redirectWithFlash('error', 'Update failed.', $result['message']);
        }

        $_SESSION['account_flash'] = [
            'type'  => 'success',
            'title' => 'Password updated',
            'body'  => 'Your password has been changed successfully.',
        ];

        header('Location: /account');
        exit;
    }

    public function deactivateAccount(): void
    {
        session_start();

        if (empty($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /account');
            exit;
        }

        $userId = (int) $_SESSION['user_id'];

        $result = ['ok' => false, 'message' => ''];
        try {
            $pdo        = DatabaseController::getDatabaseConnection();
            $repository = new UserModel($pdo);
            $result     = $repository->deactivateAccount($userId);
        } catch (PDOException $e) {
            error_log('deactivateAccount failure: ' . $e->getMessage());
            $this->redirectWithFlash('error', 'Deactivation failed.', 'A database error occurred. Please try again.');
        }

        if (!$result['ok']) {
            $this->redirectWithFlash('error', 'Deactivation failed.', $result['message']);
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();

        header('Location: /login');
        exit;
    }

    private function redirectWithFlash(string $type, string $title, string $body): void
    {
        $_SESSION['account_flash'] = [
            'type'  => $type,
            'title' => $title,
            'body'  => $body,
        ];

        header('Location: /account');
        exit;
    }
}
