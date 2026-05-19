<?php
// src/Models/Ordinance.php
namespace App\Models;

use PDO;
use PDOException;

class OrdinanceModel
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
    public function getTrending(): ?array
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
    public function get20RecentOrdinances(): array
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

    /**
     * Resolves a paginated collection of ordinances matching a specific title sequence,
     * appending pre-aggregated behavioral metrics and category classification.
     *
     * @param PDO    $pdo           An active database connection layer.
     * @param string $searchKeyword The un-wildcarded string provided by the runtime consumer.
     * @param int    $limit         Maximum number of records to return per page (default: 20).
     * @param int    $offset        Number of records to skip before collecting results (default: 0).
     * @return array{
     *     records: array<int, array{
     *         ordinance_id: int,
     *         ordinance_number: string,
     *         title: string,
     *         author_sponsor: string,
     *         series_year: int,
     *         category_name: string|null,
     *         status: string,
     *         enactment_day: int|null,
     *         enactment_month: string,
     *         enactment_year: int,
     *         pdf_file: string|null,
     *         summary: string|null,
     *         like_count: int,
     *         dislike_count: int
     *     }>,
     *     total_count: int,
     *     limit: int,
     *     offset: int
     * }                            A pagination envelope containing normalized ordinance records
     *                              and total match count for the caller to derive page boundaries.
     * @throws PDOException         If structural validation or connection boundaries fail.
     */
    public function getAllOrdinances(string $searchKeyword, int $limit = 20, int $offset = 0): array
    {
        // -------------------------------------------------------------------------
        // Guard: clamp pagination arguments to safe, non-negative integer bounds.
        // A limit of 0 is nonsensical; a floor of 1 prevents empty-page fetches.
        // -------------------------------------------------------------------------
        $limit  = max(1, $limit);
        $offset = max(0, $offset);

        // -------------------------------------------------------------------------
        // Single-roundtrip query strategy:
        //   • MetricAggregations CTE — pre-aggregates like/dislike counts so the
        //     main SELECT avoids a correlated subquery per row.
        //   • SQL_CALC_FOUND_ROWS — instructs the engine to internally track the
        //     full result-set size before LIMIT is applied, eliminating the need
        //     for a second COUNT(*) roundtrip.
        //   • FOUND_ROWS() — retrieved immediately after via fetchColumn(), which
        //     is still within the same connection context and therefore guaranteed
        //     to reflect the preceding statement's tracked count.
        //   • LEFT JOIN on Categories — fulfills category_name derivation inline
        //     rather than as a post-fetch PHP loop, keeping all data assembly
        //     server-side.
        //   • SUBSTRING_INDEX on ordinance_number — isolates only the trailing
        //     four-digit numeral segment regardless of prefix length or separator
        //     variance, which is more resilient than a fixed SUBSTRING(…, 5) offset
        //     that would silently mis-slice any non-conforming record.
        // -------------------------------------------------------------------------
        $sql = "
        WITH MetricAggregations AS (
            SELECT
                rel.ordinance_id,
                SUM(CASE WHEN rel.reaction_type = 'like'    THEN 1 ELSE 0 END) AS total_likes,
                SUM(CASE WHEN rel.reaction_type = 'dislike' THEN 1 ELSE 0 END) AS total_dislikes
            FROM
                Ordinance_Reactions rel
            GROUP BY
                rel.ordinance_id
        )
        SELECT SQL_CALC_FOUND_ROWS
            ord.ordinance_id,
            SUBSTRING_INDEX(ord.ordinance_number, '-', -1)  AS ordinance_number,
            ord.title,
            ord.author_sponsor,
            ord.series_year,
            cat.category_name                               AS category_name,
            ord.status,
            IFNULL(DAY(ord.date_enacted),       '')         AS enactment_day,
            IFNULL(MONTHNAME(ord.date_enacted), '')         AS enactment_month,
            IFNULL(YEAR(ord.date_enacted), ord.series_year) AS enactment_year,
            ord.pdf_file,
            ord.summary,
            COALESCE(metrics.total_likes,    0)             AS like_count,
            COALESCE(metrics.total_dislikes, 0)             AS dislike_count
        FROM
            Ordinances ord
        LEFT JOIN
            MetricAggregations metrics ON ord.ordinance_id = metrics.ordinance_id
        LEFT JOIN
            Categories cat             ON ord.category_id  = cat.category_id
        WHERE
            ord.title LIKE :search_pattern
            AND ord.archived_at IS NULL
        ORDER BY
            ord.date_enacted DESC,
            ord.ordinance_id DESC
        LIMIT  :limit
        OFFSET :offset;
    ";

        try {
            $stmt = $this->pdo->prepare($sql);

            $searchPattern = '%' . $searchKeyword . '%';

            // PDO requires explicit integer binding for LIMIT/OFFSET; passing them
            // inside the execute() array would bind them as strings, causing the
            // engine to ignore or misinterpret the clause on some driver versions.
            $stmt->bindValue(':search_pattern', $searchPattern,  PDO::PARAM_STR);
            $stmt->bindValue(':limit',          $limit,          PDO::PARAM_INT);
            $stmt->bindValue(':offset',         $offset,         PDO::PARAM_INT);

            $stmt->execute();

            $rawResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Retrieve the pre-calculated full match count tracked by
            // SQL_CALC_FOUND_ROWS — no second query to the Ordinances table needed.
            $totalCount = (int) $this->pdo->query('SELECT FOUND_ROWS()')->fetchColumn();

            $normalizedResults = [];

            foreach ($rawResults as $row) {
                $normalizedResults[] = [
                    'ordinance_id'    => (int)    $row['ordinance_id'],
                    'ordinance_number' =>          $row['ordinance_number'],
                    'title'           =>           $row['title'],
                    'author_sponsor'  =>           $row['author_sponsor'],
                    'series_year'     => (int)    $row['series_year'],
                    'category_name'   =>           $row['category_name'],   // null when unclassified
                    'status'          =>           $row['status'],
                    'enactment_day'   => $row['enactment_day']   !== '' ? (int) $row['enactment_day'] : null,
                    'enactment_month' =>           $row['enactment_month'],
                    'enactment_year'  => (int)    $row['enactment_year'],
                    'pdf_file'        =>           $row['pdf_file'],         // null when not yet uploaded
                    'summary'         =>           $row['summary'],          // null when not yet provided
                    'like_count'      => (int)    $row['like_count'],
                    'dislike_count'   => (int)    $row['dislike_count'],
                ];
            }

            // Return a pagination envelope so the caller can derive page boundaries
            // (e.g., total pages = ceil(total_count / limit)) without an extra call.
            return [
                'records'     => $normalizedResults,
                'total_count' => $totalCount,
                'limit'       => $limit,
                'offset'      => $offset,
            ];
        } catch (PDOException $e) {
            error_log("Database execution error within getAllOrdinances: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Resolves a collection of ordinances matching a specific title sequence, 
     * appending pre-aggregated behavioral metrics.
     *
     * @param PDO    $pdo           An active database connection layer.
     * @param string $searchKeyword The un-wildcarded string provided by the runtime consumer.
     * @return array                A multi-dimensional array of normalized ordinance records.
     * @throws PDOException         If structural validation or connection boundaries fail.
     */
    public function getOrdinancesByTitle(string $searchKeyword): array
    {
        $sql = "
        WITH MetricAggregations AS (
            SELECT 
                rel.ordinance_id,
                SUM(CASE WHEN rel.reaction_type = 'like' THEN 1 ELSE 0 END) AS total_likes,
                SUM(CASE WHEN rel.reaction_type = 'dislike' THEN 1 ELSE 0 END) AS total_dislikes
            FROM 
                Ordinance_Reactions rel
            GROUP BY 
                rel.ordinance_id
        )
        SELECT 
            ord.ordinance_id,
            SUBSTRING(ord.ordinance_number, 5) AS ordinance_number,
            ord.title,
            ord.series_year,
            IFNULL(DAY(ord.date_enacted), '') AS enactment_day,
            IFNULL(MONTHNAME(ord.date_enacted), '') AS enactment_month,
            IFNULL(YEAR(ord.date_enacted), ord.series_year) AS enactment_year,
            ord.status,
            COALESCE(metrics.total_likes, 0) AS like_count,
            COALESCE(metrics.total_dislikes, 0) AS dislike_count
        FROM 
            Ordinances ord
        LEFT JOIN 
            MetricAggregations metrics ON ord.ordinance_id = metrics.ordinance_id
        WHERE 
            ord.title LIKE :search_pattern
            AND ord.archived_at IS NULL
        ORDER BY 
            ord.date_enacted DESC, 
            ord.ordinance_id DESC;
    ";

        try {
            $stmt = $this->pdo->prepare($sql);

            // Inject SQL wildcard markers directly into the parameter context
            $searchPattern = '%' . $searchKeyword . '%';
            $stmt->execute([':search_pattern' => $searchPattern]);

            $rawResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $normalizedResults = [];

            // Type normalization phase to enforce strict application-layer typing
            foreach ($rawResults as $row) {
                $normalizedResults[] = [
                    'ordinance_id'     => (int)$row['ordinance_id'],
                    'ordinance_number' => $row['ordinance_number'],
                    'title'            => $row['title'],
                    'series_year'      => (int)$row['series_year'],
                    'enactment_day'    => $row['enactment_day'] !== '' ? (int)$row['enactment_day'] : null,
                    'enactment_month'  => $row['enactment_month'],
                    'enactment_year'   => (int)$row['enactment_year'],
                    'status'           => $row['status'],
                    'like_count'       => (int)$row['like_count'],
                    'dislike_count'    => (int)$row['dislike_count']
                ];
            }

            return $normalizedResults;
        } catch (PDOException $e) {
            // Log query state tracking locally for maintenance forensics
            error_log("Database execution error within getOrdinancesByTitle: " . $e->getMessage());
            throw $e;
        }
    }

    // ------------------------------------------------------------
    // getOrdinanceById
    //
    // Retrieves the full detail record for a single active
    // ordinance identified by its primary key.
    //
    // Joins:
    //   Categories  — resolves category_id  → category_name
    //   Barangays   — resolves barangay_id  → barangay_name
    //   Ordinance_Reactions + Reaction_Types — lifetime like /
    //     dislike totals
    //
    // Returns null when:
    //   • The ordinance_id does not exist.
    //   • The record has been soft-deleted (deleted_at IS NOT NULL).
    //
    // @param  PDO        $pdo           Active database connection.
    // @param  int        $ordinance_id  PK of the requested record.
    // @return array|null                Associative row or null.
    // ------------------------------------------------------------
    public function getOrdinanceById(int $ordinance_id): ?array
    {
        $sql = "
        SELECT
            -- Identity
            o.ordinance_id,
            REGEXP_REPLACE(o.ordinance_number, '[^0-9]', '')   AS ordinance_number,
            o.series_year,
 
            -- Content
            o.title,
            o.author_sponsor,
            o.summary,
            o.full_text,
            o.pdf_file,
            o.status,
 
            -- Related entity names (NULL-safe: LEFT JOIN)
            c.category_name,
            b.barangay_name,
 
            -- Enactment date — split for card rendering
            DATE_FORMAT(o.date_enacted, '%d')                  AS enactment_day,
            DATE_FORMAT(o.date_enacted, '%M')                  AS enactment_month,
            DATE_FORMAT(o.date_enacted, '%Y')                  AS enactment_year,
 
            -- Enactment date — formatted prose label
            DATE_FORMAT(o.date_enacted, '%d %M %Y')            AS date_enacted_fmt,
 
            -- Lifetime engagement totals
            " . _reactionCountColumns('orr', 'orr') . "
 
        FROM       Ordinances           o
 
        -- Category may be NULL; keep the row with LEFT JOIN
        LEFT JOIN  Categories           c
                       ON  c.category_id  = o.category_id
 
        -- Barangay may be NULL; keep the row with LEFT JOIN
        LEFT JOIN  Barangays            b
                       ON  b.barangay_id  = o.barangay_id
 
        -- Reactions — aggregate after join; absent rows → 0
        LEFT JOIN  Ordinance_Reactions  orr
                       ON  orr.ordinance_id    = o.ordinance_id
 
        WHERE  o.ordinance_id = :ordinance_id
          AND  o.archived_at   IS NULL
 
        -- GROUP BY all non-aggregated columns to allow SUM()
        GROUP BY
            o.ordinance_id,
            o.ordinance_number,
            o.series_year,
            o.title,
            o.author_sponsor,
            o.summary,
            o.full_text,
            o.pdf_file,
            o.status,
            o.date_enacted,
            c.category_name,
            b.barangay_name
 
        LIMIT 1
    ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':ordinance_id' => $ordinance_id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
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
