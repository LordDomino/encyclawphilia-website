<?php
// src/Models/OrdinanceRepository.php
namespace App\Models;

use PDO;
use PDOException;

class Ordinance
{
    public function __construct(private readonly PDO $pdo) {}

    /**
     * Toggles a like or dislike reaction for one user on one ordinance.
     *
     * State machine (single round-trip via INSERT ... ON DUPLICATE KEY UPDATE):
     *   No row exists          → INSERT  (action: 'added')
     *   Same reaction exists   → DELETE  (action: 'removed')
     *   Different reaction     → UPDATE  (action: 'switched')
     *
     * Returns the authoritative post-mutation state of the resource:
     *   action       — 'added' | 'removed' | 'switched'
     *   userReaction — 'like' | 'dislike' | null
     *   likes        — int, lifetime total
     *   dislikes     — int, lifetime total
     *
     * @throws PDOException on unrecoverable database failure
     */
    public function toggleReaction(
        int    $ordinanceId,
        int    $userId,
        string $reactionType    // caller must pass 'like' or 'dislike'
    ): array {
        $this->pdo->beginTransaction();

        try {
            // ── Step 1: read the current reaction state ─────────────────
            $existing = $this->pdo
                ->prepare('
                    SELECT reaction_id, reaction_type
                    FROM   Ordinance_Reactions
                    WHERE  ordinance_id = :ord AND user_id = :usr
                    LIMIT  1
                ')
                ->execute([':ord' => $ordinanceId, ':usr' => $userId])
                ? $this->fetchExisting($ordinanceId, $userId)
                : null;

            // ── Step 2: state machine ────────────────────────────────────
            if ($existing === null) {
                $this->insertReaction($ordinanceId, $userId, $reactionType);
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

            // ── Step 3: aggregate counts (inside transaction for consistency) ─
            $counts = $this->fetchAggregateCounts($ordinanceId);

            $this->pdo->commit();

            return [
                'action'       => $action,        // 'added' | 'removed' | 'switched'
                'userReaction' => $userReaction,  // 'like' | 'dislike' | null
                'likes'        => $counts['likes'],
                'dislikes'     => $counts['dislikes'],
            ];
        } catch (PDOException $e) {
            $this->pdo->inTransaction() && $this->pdo->rollBack();
            throw $e;   // controller catches and wraps in ApiResponse::error()
        }
    }

    /**
     * Retrieves the single ordinance with the highest activity score over a rolling 7-day window.
     *
     * This implementation encapsulates the analytical scoring and string normalization
     * within a single database invocation to minimize network egress overhead.
     *
     * @param PDO $pdo An active, authenticated database connection.
     * @return array|null Returns an associative array of the metrics if found, or null.
     * @throws PDOException If a structural or database engine failure occurs.
     */
    function getTrending(): ?array
    {
        $sql = "
        SELECT
            o.ordinance_id,

            -- String normalization: Strip non-digit characters at the storage engine layer
            REGEXP_REPLACE(o.ordinance_number, '[^0-9]', '') AS ordinance_number,

            o.series_year,

            -- Temporal formatting: Standardize date representation
            DATE_FORMAT(o.date_enacted, '%d %M %Y') AS date_enacted_fmt,

            o.title,

            -- Algorithmic Aggregation: Compute the rolling activity score.
            -- reaction_type is a direct ENUM('like','dislike') column; no Reaction_Types join.
            (
                COALESCE((
                    SELECT COUNT(*)
                    FROM Ordinance_Reactions r
                    WHERE r.ordinance_id = o.ordinance_id
                      AND r.reaction_type = 'like'
                      AND r.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                ), 0)
                -
                COALESCE((
                    SELECT COUNT(*)
                    FROM Ordinance_Reactions r
                    WHERE r.ordinance_id = o.ordinance_id
                      AND r.reaction_type = 'dislike'
                      AND r.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                ), 0)
                +
                COALESCE((
                    SELECT COUNT(*) * 0.5
                    FROM Comments c
                    WHERE c.ordinance_id = o.ordinance_id
                      AND c.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                ), 0)
            ) AS trending_score

        FROM Ordinances o
        WHERE o.archived_at IS NULL
        ORDER BY
            trending_score DESC,
            o.date_enacted  DESC,
            o.ordinance_id  DESC
        LIMIT 1;
    ";

        try {
            $stmt = $this->pdo->query($sql);

            // Fetch the single row as an associative array
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // Return null if the result set is completely empty
            if ($result === false) {
                return null;
            }

            // Type cast numeric returns to maintain strict type safety in application layers
            return [
                'ordinance_id'     => (int)$result['ordinance_id'],
                'ordinance_number' => $result['ordinance_number'],
                'series_year'      => (int)$result['series_year'],
                'date_enacted_fmt' => $result['date_enacted_fmt'],
                'title'            => $result['title'],
                'trending_score'   => (float)$result['trending_score']
            ];
        } catch (PDOException $e) {
            // Engineering Note: Log the exact error context ($e->getMessage()) locally
            // to a secure error tracking service before failing gracefully.
            throw $e;
        }
    }

    /**
     * Retrieves the 20 most recent ordinances that are neither archived nor future-dated,
     * along with their lifetime metric aggregates.
     *
     * @param PDO $pdo An active, authenticated database connection.
     * @return array   A multi-dimensional array of normalized ordinance records.
     * @throws PDOException If a structural database engine failure occurs.
     */
    function get20RecentOrdinances(): array
    {
        $sql = "
        SELECT
            o.ordinance_id,
            REGEXP_REPLACE(o.ordinance_number, '[^0-9]', '') AS ordinance_number,
            o.series_year,
            DATE_FORMAT(o.date_enacted, '%d') AS enactment_day,
            DATE_FORMAT(o.date_enacted, '%M') AS enactment_month,
            DATE_FORMAT(o.date_enacted, '%Y') AS enactment_year,
            o.title,
            -- reaction_type is a direct ENUM('like','dislike') column; no Reaction_Types join.
            COALESCE(SUM(CASE WHEN orr.reaction_type = 'like'    THEN 1 ELSE 0 END), 0) AS like_count,
            COALESCE(SUM(CASE WHEN orr.reaction_type = 'dislike' THEN 1 ELSE 0 END), 0) AS dislike_count
        FROM Ordinances o
        LEFT JOIN Ordinance_Reactions orr ON orr.ordinance_id = o.ordinance_id
        WHERE o.archived_at IS NULL
          AND o.date_enacted IS NOT NULL
          AND o.date_enacted <= NOW()
        GROUP BY
            o.ordinance_id,
            o.ordinance_number,
            o.series_year,
            o.date_enacted,
            o.title
        ORDER BY
            o.date_enacted DESC,
            o.ordinance_id DESC
        LIMIT 20;
        ";

        try {
            // Utilizing direct execution since no external user input requires parameter binding
            $stmt = $this->pdo->query($sql);
            $rawResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $normalizedResults = [];
            foreach ($rawResults as $row) {
                $normalizedResults[] = [
                    'ordinance_id'     => (int)$row['ordinance_id'],
                    'ordinance_number' => $row['ordinance_number'], // Preserved as string to handle leading zeros
                    'series_year'      => (int)$row['series_year'],
                    'enactment_day'    => (int)$row['enactment_day'],
                    'enactment_month'  => $row['enactment_month'],
                    'enactment_year'   => (int)$row['enactment_year'],
                    'title'            => $row['title'],
                    'like_count'       => (int)$row['like_count'],
                    'dislike_count'    => (int)$row['dislike_count']
                ];
            }

            return $normalizedResults;
        } catch (PDOException $e) {
            // Log query state tracking locally for maintenance forensics
            error_log("Database execution error within getRecentOrdinances: " . $e->getMessage());
            throw $e;
        }
    }

    // ── Private helpers ──────────────────────────────────────────────────

    private function fetchExisting(int $ordinanceId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT reaction_id, reaction_type
            FROM   Ordinance_Reactions
            WHERE  ordinance_id = :ord AND user_id = :usr
            LIMIT  1
        ');
        $stmt->execute([':ord' => $ordinanceId, ':usr' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    private function insertReaction(int $ordinanceId, int $userId, string $reactionType): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO Ordinance_Reactions (ordinance_id, user_id, reaction_type)
            VALUES (:ord, :usr, :rt)
        ');
        $stmt->execute([':ord' => $ordinanceId, ':usr' => $userId, ':rt' => $reactionType]);
    }

    private function deleteReaction(int $reactionId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM Ordinance_Reactions WHERE reaction_id = :id');
        $stmt->execute([':id' => $reactionId]);
    }

    private function updateReaction(int $reactionId, string $reactionType): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE Ordinance_Reactions
            SET    reaction_type = :rt, created_at = NOW()
            WHERE  reaction_id   = :id
        ');
        $stmt->execute([':rt' => $reactionType, ':id' => $reactionId]);
    }

    private function fetchAggregateCounts(int $ordinanceId): array
    {
        // ENUM is 'like'|'dislike' lowercase — matches schema exactly
        $stmt = $this->pdo->prepare("
            SELECT
                COALESCE(SUM(reaction_type = 'like'),    0) AS likes,
                COALESCE(SUM(reaction_type = 'dislike'), 0) AS dislikes
            FROM  Ordinance_Reactions
            WHERE ordinance_id = :ord
        ");
        $stmt->execute([':ord' => $ordinanceId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'likes'    => (int) ($row['likes']    ?? 0),
            'dislikes' => (int) ($row['dislikes'] ?? 0),
        ];
    }
}
