<?php
// src/Models/Ordinance.php
namespace App\Models;

use PDO;
use PDOException;

class CommentModel
{
    public function __construct(private readonly PDO $pdo) {}

    /**
     * getOrdinanceComments
     *
     * Retrieves comments for a given ordinance, newest-first,
     * with per-comment like and dislike counts computed directly
     * from the comment reactions schema state.
     *
     * Joins:
     *   Users             — resolves user_id → full_name
     *   Comment_Reactions — computes aggregate totals using native ENUM states
     *
     * Soft-deleted user accounts are still included so that
     * comments authored before account deletion remain visible;
     * the display layer should handle anonymisation if needed.
     *
     * Pagination is offset-based. Pass $limit = 0 to fetch all
     * comments without a LIMIT clause (use with caution on large
     * datasets).
     *
     * @param  PDO   $pdo          Active database connection.
     * @param  int   $ordinance_id FK of the parent ordinance.
     * @param  int   $limit        Max rows to return (default 50).
     * @param  int   $offset       Row offset for pagination (default 0).
     * @return array               Indexed array of associative rows.
     *                             Empty array when no comments exist.
     */
    public function getOrdinanceComments(int $ordinance_id, int $limit = 50, int $offset = 0): array
    {
        // Construct pagination safely via conditional named placeholders
        $pagination = $limit > 0
            ? ' LIMIT :limit OFFSET :offset'
            : '';

        $sql = "
        SELECT
            -- Comment identity
            cm.comment_id,
            cm.ordinance_id,
            cm.created_at,
 
            -- Comment content
            cm.comment_text,
 
            -- Author details
            cm.user_id,
            u.username,
 
            -- Per-comment engagement totals (optimized for native ENUM values)
            " . _reactionCountColumns('cr', 'cr') . "
 
        FROM       Comments          cm
 
        -- Author details extraction with structural fallback
        LEFT JOIN  Users             u
               ON  u.user_id        = cm.user_id
 
        -- Per-comment reactions isolation
        LEFT JOIN  Comment_Reactions cr
               ON  cr.comment_id    = cm.comment_id
 
        WHERE  cm.ordinance_id = :ordinance_id
 
        GROUP BY
            cm.comment_id,
            cm.ordinance_id,
            cm.created_at,
            cm.comment_text,
            cm.user_id,
            u.username
 
        -- Corrected to fulfill the 'newest-first' operational mandate
        ORDER BY cm.created_at DESC,
                 cm.comment_id DESC
    " . $pagination;

        $stmt = $this->pdo->prepare($sql);

        // Explicit value binding enforces type correctness at the driver layer
        $stmt->bindValue(':ordinance_id', $ordinance_id, PDO::PARAM_INT);

        if ($limit > 0) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Toggles a like or dislike reaction for one user on one comment.
     * Mirrors OrdinanceModel::toggleReaction() state machine exactly.
     *
     * @throws PDOException on unrecoverable database failure
     */
    public function toggleReaction(
        int    $commentId,
        int    $userId,
        string $reactionType
    ): array {
        $this->pdo->beginTransaction();

        try {
            $existing = $this->fetchExistingReaction($commentId, $userId);

            if ($existing === null) {
                $this->insertReaction($commentId, $userId, $reactionType);
                $action       = 'added';
                $userReaction = $reactionType;
            } elseif ($existing['reaction_type'] === $reactionType) {
                $this->deleteReaction((int) $existing['reaction_id']);
                $action       = 'removed';
                $userReaction = null;
            } else {
                $this->updateReaction((int) $existing['reaction_id'], $reactionType);
                $action       = 'switched';
                $userReaction = $reactionType;
            }

            $counts = $this->fetchAggregateCounts($commentId);

            $this->pdo->commit();

            return [
                'action'       => $action,
                'userReaction' => $userReaction,
                'likes'        => $counts['likes'],
                'dislikes'     => $counts['dislikes'],
            ];
        } catch (PDOException $e) {
            $this->pdo->inTransaction() && $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Returns the current user's reaction type for each of the supplied
     * comment IDs in a single query — keyed by comment_id for O(1) lookup
     * in the view layer.
     *
     * @param  int   $userId
     * @param  int[] $commentIds
     * @return array<int, string>  e.g. [42 => 'like', 87 => 'dislike']
     */
    public function getUserReactionsForComments(int $userId, array $commentIds): array
    {
        if (empty($commentIds)) {
            return [];
        }

        // Build positional placeholders — safe because values are cast to int
        $placeholders = implode(', ', array_fill(0, count($commentIds), '?'));

        $stmt = $this->pdo->prepare("
            SELECT comment_id, reaction_type
            FROM   Comment_Reactions
            WHERE  user_id    = ?
            AND  comment_id IN ({$placeholders})
        ");

        // user_id first, then the comment IDs in the same order as placeholders
        $stmt->execute([$userId, ...array_map('intval', $commentIds)]);

        $map = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $map[(int) $row['comment_id']] = $row['reaction_type'];
        }

        return $map;
    }

    // ── Private helpers ──────────────────────────────────────────────────

    private function fetchExistingReaction(int $commentId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare('
        SELECT reaction_id, reaction_type
        FROM   Comment_Reactions
        WHERE  comment_id = :cid AND user_id = :usr
        LIMIT  1
    ');
        $stmt->execute([':cid' => $commentId, ':usr' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    private function insertReaction(int $commentId, int $userId, string $reactionType): void
    {
        $stmt = $this->pdo->prepare('
        INSERT INTO Comment_Reactions (comment_id, user_id, reaction_type)
        VALUES (:cid, :usr, :rt)
    ');
        $stmt->execute([':cid' => $commentId, ':usr' => $userId, ':rt' => $reactionType]);
    }

    private function deleteReaction(int $reactionId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM Comment_Reactions WHERE reaction_id = :id');
        $stmt->execute([':id' => $reactionId]);
    }

    private function updateReaction(int $reactionId, string $reactionType): void
    {
        $stmt = $this->pdo->prepare('
        UPDATE Comment_Reactions
        SET    reaction_type = :rt, created_at = NOW()
        WHERE  reaction_id   = :id
    ');
        $stmt->execute([':rt' => $reactionType, ':id' => $reactionId]);
    }

    private function fetchAggregateCounts(int $commentId): array
    {
        $stmt = $this->pdo->prepare("
        SELECT
            COALESCE(SUM(reaction_type = 'like'),    0) AS likes,
            COALESCE(SUM(reaction_type = 'dislike'), 0) AS dislikes
        FROM  Comment_Reactions
        WHERE comment_id = :cid
    ");
        $stmt->execute([':cid' => $commentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'likes'    => (int) ($row['likes']    ?? 0),
            'dislikes' => (int) ($row['dislikes'] ?? 0),
        ];
    }
}
