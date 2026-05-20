<?php

namespace Controllers;

require_once __DIR__ . '/../Core/ApiResponse.php';

use Core\ApiResponse;
use App\Models\OrdinanceModel;
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
            $pdo        = getDatabaseConnection();
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
}
