<?php
// src/Models/Ordinance.php
namespace App\Models;

require_once __DIR__ . '/procedures.php';

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
     * Retrieves a paginated slice of ordinances matching an optional title keyword,
     * ordered by enactment date descending.
     *
     * When $searchKeyword is empty, all active ordinances are returned (default browse).
     * When $searchKeyword is provided, results are filtered by title LIKE match.
     *
     * @param PDO    $pdo            Active database connection.
     * @param string $searchKeyword  Raw (un-wildcarded) keyword. Empty string = no filter.
     * @param int    $limit          Rows per page.
     * @param int    $offset         Row offset for the current page.
     * @return array{rows: array, total: int}
     *     rows  — Normalized ordinance records for the requested slice.
     *     total — Total matching rows across all pages (for pagination math).
     * @throws PDOException On structural or connection failure.
     */
    public function getOrdinancesByTitle(
        string $searchKeyword = '',
        int    $limit         = 20,
        int    $offset        = 0
    ): array {
        $hasKeyword     = $searchKeyword !== '';
        $whereKeyword   = $hasKeyword ? 'AND ord.title LIKE :search_pattern' : '';

        // ── Total count (for pagination) ──────────────────────────────────
        $countSql = "
        SELECT COUNT(*) AS total
        FROM   Ordinances ord
        WHERE  ord.archived_at IS NULL
        {$whereKeyword}
        ";

        $countStmt = $this->pdo->prepare($countSql);
        if ($hasKeyword) {
            $countStmt->bindValue(':search_pattern', '%' . $searchKeyword . '%');
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        // When limit is 0, the caller only wants the total count
        if ($limit === 0) {
            return ['rows' => [], 'total' => $total];
        }

        // ── Paginated result slice ─────────────────────────────────────────
        $dataSql = "
        WITH MetricAggregations AS (
            SELECT
                rel.ordinance_id,
                SUM(CASE WHEN rel.reaction_type = 'like'    THEN 1 ELSE 0 END) AS total_likes,
                SUM(CASE WHEN rel.reaction_type = 'dislike' THEN 1 ELSE 0 END) AS total_dislikes
            FROM Ordinance_Reactions rel
            GROUP BY rel.ordinance_id
        )
        SELECT
            ord.ordinance_id,
            SUBSTRING(ord.ordinance_number, 5)              AS ordinance_number,
            ord.title,
            ord.series_year,
            IFNULL(DAY(ord.date_enacted),       '')         AS enactment_day,
            IFNULL(MONTHNAME(ord.date_enacted), '')         AS enactment_month,
            IFNULL(YEAR(ord.date_enacted), ord.series_year) AS enactment_year,
            ord.status,
            COALESCE(metrics.total_likes,    0)             AS like_count,
            COALESCE(metrics.total_dislikes, 0)             AS dislike_count
        FROM Ordinances ord
        LEFT JOIN MetricAggregations metrics
               ON metrics.ordinance_id = ord.ordinance_id
        WHERE  ord.archived_at IS NULL
        {$whereKeyword}
        ORDER BY ord.date_enacted DESC,
                 ord.ordinance_id  DESC
        LIMIT  :limit
        OFFSET :offset
    ";

        $dataStmt = $this->pdo->prepare($dataSql);
        if ($hasKeyword) {
            $dataStmt->bindValue(':search_pattern', '%' . $searchKeyword . '%');
        }
        $dataStmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $dataStmt->execute();

        $rawResults = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

        // ── Type normalization ─────────────────────────────────────────────
        $normalized = [];
        foreach ($rawResults as $row) {
            $normalized[] = [
                'ordinance_id'     => (int) $row['ordinance_id'],
                'ordinance_number' => $row['ordinance_number'],
                'title'            => $row['title'],
                'series_year'      => (int) $row['series_year'],
                'enactment_day'    => $row['enactment_day'] !== '' ? (int) $row['enactment_day'] : null,
                'enactment_month'  => $row['enactment_month'],
                'enactment_year'   => (int) $row['enactment_year'],
                'status'           => $row['status'],
                'like_count'       => (int) $row['like_count'],
                'dislike_count'    => (int) $row['dislike_count'],
            ];
        }

        return [
            'rows'  => $normalized,
            'total' => $total,
        ];
    }
    /**
     * searchOrdinancesByCategory
     *
     * Retrieves a paginated, filtered list of non-archived ordinances belonging
     * to one or more categories.  Designed for a single round-trip to MariaDB
     * using a single parameterised SQL statement (no PHP-side post-processing
     * loops, no secondary queries).
     *
     * Business-rule compliance
     * ─────────────────────────
     * • Archived ordinances (archived_at IS NOT NULL) are never surfaced to
     *   public-facing callers.  Pass $includeArchived = true only from a
     *   privileged administrator view.
     * • Write-Once fields (ordinance_number, title, author_sponsor, series_year)
     *   are returned read-only; this layer never issues UPDATE on them.
     * • Soft-deleted users are still referenced by their anonymised username
     *   (the DB already stores the hashed replacement on deletion), so no
     *   special handling is needed here.
     * • The result includes aggregate like/dislike counts and total comment
     *   counts so the caller does not need secondary queries.
     *
     * @param PDO        $pdo              Active PDO connection.
     * @param int|int[]  $categoryIds      Single category ID or array of IDs.
     *                                     Pass an empty array [] to match ALL
     *                                     categories (including uncategorised).
     * @param string     $searchKeyword    Optional full-text keyword matched
     *                                     against title, summary, and full_text.
     *                                     Pass '' to skip keyword filtering.
     * @param string[]   $statuses         Filter by ordinance status values.
     *                                     Allowed: 'Pending','Active','Repealed','Amended'.
     *                                     Pass [] to include all statuses.
     * @param int|null   $barangayId       Optional barangay filter. Pass null to skip.
     * @param string     $sortBy           Column to sort by.
     *                                     Allowed: 'date_enacted','created_at','title','series_year'.
     *                                     Defaults to 'created_at'.
     * @param string     $sortDir          'ASC' or 'DESC'. Defaults to 'DESC'.
     * @param int        $limit            Page size (1–100). Defaults to 20.
     * @param int        $offset           Zero-based row offset for pagination. Defaults to 0.
     * @param bool       $includeArchived  When true, archived records are included.
     *                                     Must only be true for privileged admin views.
     *
     * @return array{
     *   total: int,
     *   limit: int,
     *   offset: int,
     *   ordinances: list<array<string, mixed>>
     * }
     *
     * @throws InvalidArgumentException  On illegal parameter values.
     * @throws PDOException              On database errors.
     */
    function searchOrdinancesByCategory(
        int|array  $categoryIds,
        string     $searchKeyword  = '',
        array      $statuses       = [],
        ?int       $barangayId     = null,
        string     $sortBy         = 'created_at',
        string     $sortDir        = 'DESC',
        int        $limit          = 20,
        int        $offset         = 0,
        bool       $includeArchived = false
    ): array {

        /* ── 1. Input validation ─────────────────────────────────────────────── */

        $allowedSortColumns = ['date_enacted', 'created_at', 'title', 'series_year'];
        $sortBy  = strtolower(trim($sortBy));
        $sortDir = strtoupper(trim($sortDir));

        if (!in_array($sortBy, $allowedSortColumns, true)) {
            throw new \InvalidArgumentException(
                "Invalid sortBy value '{$sortBy}'. Allowed: " . implode(', ', $allowedSortColumns)
            );
        }
        if (!in_array($sortDir, ['ASC', 'DESC'], true)) {
            throw new \InvalidArgumentException("sortDir must be 'ASC' or 'DESC'.");
        }

        $limit  = max(1, min(100, (int) $limit));
        $offset = max(0, (int) $offset);

        $allowedStatuses = ['Pending', 'Active', 'Repealed', 'Amended'];
        foreach ($statuses as $s) {
            if (!in_array($s, $allowedStatuses, true)) {
                throw new \InvalidArgumentException(
                    "Invalid status value '{$s}'. Allowed: " . implode(', ', $allowedStatuses)
                );
            }
        }

        // Normalise $categoryIds → always a plain array of ints.
        $categoryIds = is_array($categoryIds) ? array_values($categoryIds) : [$categoryIds];
        $categoryIds = array_filter($categoryIds, fn($id) => is_int($id) && $id > 0);
        $categoryIds = array_values(array_unique($categoryIds));

        /* ── 2. Build WHERE clauses & parameter list ─────────────────────────── */

        $conditions = [];
        $params     = [];

        // 2a. Archive guard (core business rule).
        if (!$includeArchived) {
            $conditions[] = 'o.archived_at IS NULL';
        }

        // 2b. Category filter.
        //     Empty $categoryIds → match everything (no category condition added).
        if (!empty($categoryIds)) {
            $placeholders  = implode(', ', array_fill(0, count($categoryIds), '?'));
            $conditions[]  = "o.category_id IN ({$placeholders})";
            foreach ($categoryIds as $cid) {
                $params[] = (int) $cid;
            }
        }

        // 2c. Status filter.
        if (!empty($statuses)) {
            $placeholders = implode(', ', array_fill(0, count($statuses), '?'));
            $conditions[] = "o.status IN ({$placeholders})";
            foreach ($statuses as $s) {
                $params[] = $s;
            }
        }

        // 2d. Barangay filter.
        if ($barangayId !== null) {
            $conditions[] = 'o.barangay_id = ?';
            $params[]     = (int) $barangayId;
        }

        // 2e. Keyword search (LIKE-based; upgrade to FULLTEXT if needed at scale).
        $keyword = trim($searchKeyword);
        if ($keyword !== '') {
            $conditions[] = '(o.title LIKE ? OR o.summary LIKE ? OR o.full_text LIKE ?)';
            $likeToken    = '%' . addcslashes($keyword, '%_\\') . '%';
            $params[]     = $likeToken;
            $params[]     = $likeToken;
            $params[]     = $likeToken;
        }

        $whereClause = $conditions
            ? 'WHERE ' . implode(' AND ', $conditions)
            : '';

        /* ── 3. Qualify the sort column with the table alias ─────────────────── */

        // title needs a LOWER() wrapper for case-insensitive alphabetical sort.
        $orderExpr = $sortBy === 'title'
            ? "LOWER(o.title) {$sortDir}"
            : "o.{$sortBy} {$sortDir}";

        // Secondary stable sort so paging is deterministic.
        $orderExpr .= ', o.ordinance_id ASC';

        /* ── 4. Build the single-trip SQL ─────────────────────────────────────── */
        //
        // Strategy: one CTE (`filtered`) captures the matching ordinance IDs and
        // the total row count via COUNT(*) OVER () (window function — MariaDB 10.2+).
        // The outer SELECT then joins the enriched data (category, barangay, tags,
        // reaction aggregates, comment count) only for the current page's rows.
        //
        // Tags are collapsed into a JSON array in-database so the caller receives
        // a single row per ordinance with no PHP-side grouping needed.
        //
        // All aggregates (likes, dislikes, comment count) are computed with
        // correlated sub-queries rather than multiple GROUP BY joins to avoid
        // row-multiplication across different one-to-many relationships.
        // ─────────────────────────────────────────────────────────────────────────

        $sql = "
        WITH filtered AS (
            SELECT
                o.ordinance_id,
                COUNT(*) OVER () AS total_count
            FROM Ordinances o
            {$whereClause}
            ORDER BY {$orderExpr}
            LIMIT ? OFFSET ?
        )
        SELECT
            /* ── pagination meta ── */
            f.total_count,

            /* ── write-once identity fields ── */
            o.ordinance_id,
            o.ordinance_number,
            o.title,
            o.author_sponsor,
            o.series_year,

            /* ── mutable / write-once-after-null fields ── */
            o.status,
            o.date_enacted,
            o.pdf_file,
            o.summary,
            o.full_text,
            o.created_at,
            o.updated_at,
            o.archived_at,

            /* ── category info ── */
            c.category_id,
            c.category_name,
            c.description   AS category_description,

            /* ── barangay info ── */
            b.barangay_id,
            b.barangay_name,

            /* ── tags collapsed to a JSON array ── */
            (
                SELECT JSON_ARRAYAGG(t.tag_name)
                FROM   Ordinance_Tags ot
                JOIN   Tags t ON t.tag_id = ot.tag_id
                WHERE  ot.ordinance_id = o.ordinance_id
            ) AS tags,

            /* ── reaction aggregates ── */
            (
                SELECT COUNT(*)
                FROM   Ordinance_Reactions r
                WHERE  r.ordinance_id  = o.ordinance_id
                  AND  r.reaction_type = 'like'
            ) AS like_count,

            (
                SELECT COUNT(*)
                FROM   Ordinance_Reactions r
                WHERE  r.ordinance_id  = o.ordinance_id
                  AND  r.reaction_type = 'dislike'
            ) AS dislike_count,

            /* ── comment count ── */
            (
                SELECT COUNT(*)
                FROM   Comments cm
                WHERE  cm.ordinance_id = o.ordinance_id
            ) AS comment_count

        FROM filtered f
        JOIN Ordinances  o ON o.ordinance_id  = f.ordinance_id
        LEFT JOIN Categories c ON c.category_id  = o.category_id
        LEFT JOIN Barangays  b ON b.barangay_id  = o.barangay_id
        ORDER BY {$orderExpr}
    ";

        /* ── 5. Bind pagination params (appended after WHERE params) ─────────── */

        $params[] = $limit;
        $params[] = $offset;

        /* ── 6. Execute ──────────────────────────────────────────────────────── */

        $stmt = $this->pdo->prepare($sql);

        // Bind everything positionally; PDO will handle type inference.
        foreach ($params as $index => $value) {
            // PDO positional params are 1-indexed.
            $stmt->bindValue($index + 1, $value);
        }

        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        /* ── 7. Post-process: decode JSON tag arrays ─────────────────────────── */
        //
        // JSON_ARRAYAGG returns a JSON string; decode it here so the caller
        // always receives a PHP array (empty array when no tags exist).
        //
        $total = 0;

        foreach ($rows as &$row) {
            $total      = (int) $row['total_count'];  // same value on every row
            unset($row['total_count']);                // strip the meta column

            $row['tags']          = isset($row['tags'])
                ? (json_decode($row['tags'], true) ?? [])
                : [];
            $row['like_count']    = (int) $row['like_count'];
            $row['dislike_count'] = (int) $row['dislike_count'];
            $row['comment_count'] = (int) $row['comment_count'];
        }
        unset($row);

        /* ── 8. Return structured result ─────────────────────────────────────── */

        return [
            'total'      => $total,
            'limit'      => $limit,
            'offset'     => $offset,
            'ordinances' => $rows,
        ];
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


    /**
     * Executes a single-trip database insertion for a new ordinance record.
     * Enforces schema defaults and protects data integrity under the 
     * "Write Once, Transparent Forever" architecture.
     *
     * @param PDO   $pdo  An active PDO connection instance configured with error-reporting attributes.
     * @param array $data Associative array containing raw user input values.
     * @return string     The auto-incremented primary key (ordinance_id) of the recorded entity.
     * @throws PDOException If an operational error or a unique constraint violation occurs.
     */
    public function addOrdinanceProcedure(array $data): string
    {
        // Placeholder configuration for the targeted execution interface
        // e.g., Actions originating from custom routing URLs: /ordinance/register
        $targetEndpointPlaceholder = "encyclawphilia-valenzuela.local/add-ordinance";

        // Prepare the single-trip atomic INSERT instruction
        $sql = "INSERT INTO Ordinances (
                ordinance_number, 
                title, 
                author_sponsor, 
                series_year, 
                category_id, 
                barangay_id, 
                status, 
                date_enacted, 
                pdf_file, 
                summary, 
                full_text
            ) VALUES (
                :ordinance_number, 
                :title, 
                :author_sponsor, 
                :series_year, 
                :category_id, 
                :barangay_id, 
                :status, 
                :date_enacted, 
                :pdf_file, 
                :summary, 
                :full_text
            )";

        try {
            $stmt = $this->pdo->prepare($sql);

            // Bind strictly mandatory fixed components
            $stmt->bindValue(':ordinance_number', $data['ordinance_number'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':title', $data['title'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':author_sponsor', $data['author_sponsor'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':series_year', $data['series_year'] ?? null, PDO::PARAM_STR);

            // Bind mutable/optional parameters using structural null checks
            $stmt->bindValue(':category_id', $data['category_id'] ?? null, is_null($data['category_id'] ?? null) ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':barangay_id', $data['barangay_id'] ?? null, is_null($data['barangay_id'] ?? null) ? PDO::PARAM_NULL : PDO::PARAM_INT);

            // Enforce the business rule: default status to 'Pending' if unspecified
            $status = $data['status'] ?? 'Pending';
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);

            $stmt->bindValue(':date_enacted', $data['date_enacted'] ?? null, is_null($data['date_enacted'] ?? null) ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':pdf_file', $data['pdf_file'] ?? null, is_null($data['pdf_file'] ?? null) ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':summary', $data['summary'] ?? null, is_null($data['summary'] ?? null) ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':full_text', $data['full_text'] ?? null, is_null($data['full_text'] ?? null) ? PDO::PARAM_NULL : PDO::PARAM_STR);

            $stmt->execute();

            // Return the identifier generated by the relational engine
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            // Intercept standard State 23000 (Integrity Constraint Violation / Duplication)
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), '1062')) {
                throw new PDOException("Data Integrity Fault: The ordinance identifier code already exists within the system.", 23000, $e);
            }
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
