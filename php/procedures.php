<?php

// ------------------------------------------------------------
// INTERNAL HELPER: reaction-count subexpression
//
// Returns the two COALESCE(SUM(CASE …)) column expressions
// shared by several queries so the SQL stays DRY.
//
// @param  string $join_alias  The Ordinance_Reactions table alias
//                             used in the surrounding query.
// @param  string $rt_alias    The Reaction_Types table alias.
// @return string              Two comma-separated SQL column
//                             expressions with trailing comma.
// ------------------------------------------------------------
function _reactionCountColumns(string $join_alias = 'orr', string $rt_alias = 'rt'): string
{
    return "
        COALESCE(SUM(CASE WHEN LOWER({$rt_alias}.reaction_type) = 'like'    THEN 1 ELSE 0 END), 0) AS like_count,
        COALESCE(SUM(CASE WHEN LOWER({$rt_alias}.reaction_type) = 'dislike' THEN 1 ELSE 0 END), 0) AS dislike_count
    ";
}

/**
 * Executes user registration logic previously handled by sp_UserSignUp.
 *
 * @param PDO    $pdo          Authenticated PDO instance.
 * @param string $username     Raw name string.
 * @param string $email        Raw email address string.
 * @param string $passwordHash Pre-hashed password string.
 * @param int    $roleId       Target role identifier.
 * @return array Contains 'user_id' (int) and 'message' (string).
 */
function registerUser(PDO $pdo, string $username, string $email, string $passwordHash, int $roleId): array
{
    // Replicating SQL local variable initialization & data transformation
    $trimmedName  = trim($username);
    $trimmedEmail = strtolower(trim($email));
    $cleanPassword = trim($passwordHash);

    // 1. Structural Validation Phase (Guard Clauses)
    if ($trimmedName === '') {
        return ['user_id' => 0, 'message' => 'ERROR: Full name is required.'];
    }

    if ($trimmedEmail === '') {
        return ['user_id' => 0, 'message' => 'ERROR: Email is required.'];
    }

    if ($cleanPassword === '') {
        return ['user_id' => 0, 'message' => 'ERROR: Password is required.'];
    }

    try {
        // Begin transaction block to isolate analytical reads and subsequent write operations
        $pdo->beginTransaction();

        // 4. Execution Phase: Mutating Schema State
        $insertStmt = $pdo->prepare("
            INSERT INTO Users (username, email, password_hash, role_id)
            VALUES (:username, :email, :password_hash, :role_id)
        ");
        
        $insertStmt->execute([
            ':username'     => $trimmedName,
            ':email'         => $trimmedEmail,
            ':password_hash' => $passwordHash, // Retaining original hash structure
            ':role_id'       => $roleId
        ]);

        // Capture state details before confirming data persistence
        $newUserId = (int)$pdo->lastInsertId();

        // Commit transaction blocks updates to disk atomically
        $pdo->commit();

        return [
            'user_id' => $newUserId,
            'message' => "SUCCESS: User registered with ID {$newUserId}."
        ];

    } catch (PDOException $e) {
        // Safeguard against partial executions or engine failures
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        // Log $e->getMessage() privately for engineering forensics
        return [
            'user_id' => 0,
            'message' => 'ERROR: System failure encountered during compilation or persistence.'
        ];
    }
}

/**
 * Validates user credentials against stored relational identities.
 *
 * @param PDO    $pdo      Active connection instance.
 * @param string $email    Raw client-supplied identification string.
 * @param string $password Raw client-supplied credential string.
 * @return array           An associative array denoting outcome status and user context.
 */
function verifyUserLogin(PDO $pdo, string $email, string $password): array
{
    $sql = "
        SELECT 
            user_id, 
            username, 
            password_hash, 
            role_id 
        FROM Users 
        WHERE email = :email 
          AND deleted_at IS NULL 
        LIMIT 1;
    ";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => strtolower(trim($email))]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Terminate early if the identification string does not match any records
        if (!$user) {
            return ['authenticated' => false, 'message' => 'ERROR: Invalid credentials.'];
        }

        // Cryptographic evaluation of the plaintext input against the storage hash
        if (!password_verify($password, $user['password_hash'])) {
            return ['authenticated' => false, 'message' => 'ERROR: Invalid credentials.'];
        }

        // Return a safe subset of the identity payload on successful matching
        return [
            'authenticated' => true,
            'message'       => 'SUCCESS: Authentication verified.',
            'user'          => [
                'id'        => (int)$user['user_id'],
                'username' => $user['username'],
                'role_id'   => (int)$user['role_id']
            ]
        ];

    } catch (PDOException $e) {
        error_log("Authentication routine system fault: " . $e->getMessage());
        return ['authenticated' => false, 'message' => 'ERROR: System infrastructure failure.'];
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
function getTrendingOrdinance(PDO $pdo): ?array
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
        $stmt = $pdo->query($sql);

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
 * Resolves a collection of ordinances matching a specific title sequence, 
 * appending pre-aggregated behavioral metrics.
 *
 * @param PDO    $pdo           An active database connection layer.
 * @param string $searchKeyword The un-wildcarded string provided by the runtime consumer.
 * @return array                A multi-dimensional array of normalized ordinance records.
 * @throws PDOException         If structural validation or connection boundaries fail.
 */
function getOrdinancesByTitle(PDO $pdo, string $searchKeyword): array
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
        $stmt = $pdo->prepare($sql);

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

/**
 * Retrieves the 20 most recent ordinances that are neither archived nor future-dated,
 * along with their lifetime metric aggregates.
 *
 * @param PDO $pdo An active, authenticated database connection.
 * @return array   A multi-dimensional array of normalized ordinance records.
 * @throws PDOException If a structural database engine failure occurs.
 */
function getRecentOrdinances(PDO $pdo): array
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
        $stmt = $pdo->query($sql);
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
function getOrdinanceById(PDO $pdo, int $ordinance_id): ?array
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
 
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':ordinance_id' => $ordinance_id]);
 
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
 
    return $row !== false ? $row : null;
}

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
function getOrdinanceComments(PDO $pdo, int $ordinance_id, int $limit = 50, int $offset = 0): array
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
 
    $stmt = $pdo->prepare($sql);
    
    // Explicit value binding enforces type correctness at the driver layer
    $stmt->bindValue(':ordinance_id', $ordinance_id, PDO::PARAM_INT);
    
    if ($limit > 0) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    }
 
    $stmt->execute();
 
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}