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
}
