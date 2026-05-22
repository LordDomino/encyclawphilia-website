<?php
namespace App\Models;

use PDO;
use PDOException;

class UserModel
{
    private PDO $db;

    public function __construct(PDO $databaseConnection)
    {
        $this->db = $databaseConnection;
    }

    /**
     * Registers a new user and persists their record to the database.
     *
     * @param string $username     Raw name string.
     * @param string $email        Raw email address string.
     * @param string $passwordHash Pre-hashed password string.
     * @param int    $roleId       Target role identifier.
     * @return array               Contains 'user_id' (int) and 'message' (string).
     */
    public function register(string $username, string $email, string $passwordHash, int $roleId): array
    {
        $trimmedName   = trim($username);
        $trimmedEmail  = strtolower(trim($email));
        $cleanPassword = trim($passwordHash);

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
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                INSERT INTO Users (username, email, password_hash, role_id)
                VALUES (:username, :email, :password_hash, :role_id)
            ");

            $stmt->execute([
                ':username'      => $trimmedName,
                ':email'         => $trimmedEmail,
                ':password_hash' => $passwordHash,
                ':role_id'       => $roleId,
            ]);

            $newUserId = (int) $this->db->lastInsertId();

            $this->db->commit();

            return [
                'user_id' => $newUserId,
                'message' => "SUCCESS: User registered with ID {$newUserId}.",
            ];
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
                if ($e->errorInfo[1] === 1062) {
                    return [
                        'user_id' => 0,
                        'message' => "Username or email address is already in use."
                    ];
                }
            }

            return [
                'user_id' => 0,
                'message' => 'ERROR: Registration failed due to a system error.',
            ];
        }
    }

    /**
     * Validates user credentials against stored relational identities.
     *
     * @param string $email    Raw client-supplied email address.
     * @param string $password Raw client-supplied plaintext password.
     * @return array           Outcome status and safe user context on success.
     */
    public function verifyLogin(string $email, string $password): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT user_id, username, password_hash, role_id
                FROM Users
                WHERE email = :email
                  AND deleted_at IS NULL
                LIMIT 1
            ");

            $stmt->execute([':email' => strtolower(trim($email))]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ['authenticated' => false, 'message' => 'ERROR: Invalid credentials.'];
            }

            if (!password_verify($password, $user['password_hash'])) {
                return ['authenticated' => false, 'message' => 'ERROR: Invalid credentials.'];
            }

            return [
                'authenticated' => true,
                'message'       => 'SUCCESS: Authentication verified.',
                'user'          => [
                    'id'       => (int) $user['user_id'],
                    'username' => $user['username'],
                    'role_id'  => (int) $user['role_id'],
                ],
            ];
        } catch (PDOException $e) {
            error_log("User::verifyLogin() failure: " . $e->getMessage());

            return ['authenticated' => false, 'message' => 'ERROR: System infrastructure failure.'];
        }
    }

    public function getUserById(int $userId): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT user_id, username, email, password_hash, deleted_at FROM Users WHERE user_id = :user_id AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([':user_id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user !== false ? $user : null;
        } catch (PDOException $e) {
            error_log("User::getUserById() failure: " . $e->getMessage());
            return null;
        }
    }

    public function verifyPasswordById(int $userId, string $password): bool
    {
        $user = $this->getUserById($userId);
        if (!$user || !isset($user['password_hash'])) {
            return false;
        }

        return password_verify($password, $user['password_hash']);
    }

    public function updateUsername(int $userId, string $username): array
    {
        $trimmed = trim($username);

        if ($trimmed === '') {
            return ['ok' => false, 'message' => 'Username is required.'];
        }

        $length = mb_strlen($trimmed);
        if ($length < 3 || $length > 100) {
            return ['ok' => false, 'message' => 'Username must be 3 to 100 characters long.'];
        }

        try {
            $stmt = $this->db->prepare("UPDATE Users SET username = :username WHERE user_id = :user_id AND deleted_at IS NULL");
            $stmt->execute([
                ':username' => $trimmed,
                ':user_id'  => $userId,
            ]);

            if ($stmt->rowCount() === 0) {
                return ['ok' => false, 'message' => 'Unable to update username.'];
            }

            return ['ok' => true, 'message' => 'Username updated successfully.'];
        } catch (PDOException $e) {
            if (isset($e->errorInfo[1]) && $e->errorInfo[1] === 1062) {
                return ['ok' => false, 'message' => 'This username is already taken.'];
            }

            error_log("User::updateUsername() failure: " . $e->getMessage());
            return ['ok' => false, 'message' => 'A system error occurred while updating username.'];
        }
    }

    public function updatePassword(int $userId, string $passwordHash): array
    {
        if (trim($passwordHash) === '') {
            return ['ok' => false, 'message' => 'Password hash cannot be empty.'];
        }

        try {
            $stmt = $this->db->prepare("UPDATE Users SET password_hash = :password_hash WHERE user_id = :user_id AND deleted_at IS NULL");
            $stmt->execute([
                ':password_hash' => $passwordHash,
                ':user_id'       => $userId,
            ]);

            return ['ok' => true, 'message' => 'Password updated successfully.'];
        } catch (PDOException $e) {
            error_log("User::updatePassword() failure: " . $e->getMessage());
            return ['ok' => false, 'message' => 'A system error occurred while updating password.'];
        }
    }

    public function deactivateAccount(int $userId): array
    {
        try {
            $stmt = $this->db->prepare("UPDATE Users SET deleted_at = NOW() WHERE user_id = :user_id AND deleted_at IS NULL");
            $stmt->execute([':user_id' => $userId]);

            if ($stmt->rowCount() === 0) {
                return ['ok' => false, 'message' => 'Unable to deactivate account.'];
            }

            return ['ok' => true, 'message' => 'Account deactivated successfully.'];
        } catch (PDOException $e) {
            error_log("User::deactivateAccount() failure: " . $e->getMessage());
            return ['ok' => false, 'message' => 'A system error occurred while deactivating account.'];
        }
    }

    /**
     * Handles user reactions (Like/Dislike) on ordinances using a
     * State-Oriented Single-Trip Protocol.
     *
     * Architectural contract: This method executes the full reaction state machine
     * and returns the absolute, authoritative post-mutation state of the resource
     * within a single HTTP round trip. The client should treat the returned payload
     * as the ground truth for UI reconciliation (Optimistic UI sync).
     *
     * Concurrency control: The initial SELECT uses FOR UPDATE to acquire a
     * pessimistic row-level lock, preventing duplicate-insert races under high
     * concurrency. Aggregate counts are computed within the same transaction
     * boundary, guaranteeing they reflect all uncommitted mutations of this session
     * before the final COMMIT.
     *
     * @param int    $ordinanceId  The ID of the targeted ordinance.
     * @param int    $userId       The ID of the reacting user.
     * @param string $reactionType The reaction type identifier: 'LIKE' or 'DISLIKE'.
     *
     * @return array{
     *     status:  string,
     *     action:  string,
     *     message: string,
     *     data: array{
     *         user_reaction: string|null,
     *         aggregates: array{
     *             likes:    int,
     *             dislikes: int
     *         }
     *     }
     * }
     */
    public function reactToOrdinance(int $ordinanceId, int $userId, string $reactionType): array
    {
        try {
            $this->db->beginTransaction();

            // ----------------------------------------------------------------
            // STEP 1 — Pessimistic read: fetch existing reaction state and lock
            // the row (or the absence of one) for the duration of this transaction.
            //
            // FOR UPDATE prevents concurrent sessions from reading the same
            // absent row and racing to INSERT, which would produce a duplicate-key
            // violation under high concurrency.
            // ----------------------------------------------------------------
            $selectStmt = $this->db->prepare("
            SELECT reaction_id, reaction_type
            FROM   Ordinance_Reactions
            WHERE  ordinance_id = :ordinance_id
              AND  user_id      = :user_id
            LIMIT  1
            FOR UPDATE
        ");

            $selectStmt->execute([
                ':ordinance_id' => $ordinanceId,
                ':user_id'      => $userId,
            ]);

            $existingReaction = $selectStmt->fetch(PDO::FETCH_ASSOC);

            // ----------------------------------------------------------------
            // STEP 2 — State machine: three mutually exclusive transition paths.
            // ----------------------------------------------------------------
            if (!$existingReaction) {
                // State A — No prior reaction: INSERT a new record.
                $mutationStmt = $this->db->prepare("
                INSERT INTO Ordinance_Reactions (ordinance_id, user_id, reaction_type)
                VALUES (:ordinance_id, :user_id, :reaction_type)
            ");
                $mutationStmt->execute([
                    ':ordinance_id'  => $ordinanceId,
                    ':user_id'       => $userId,
                    ':reaction_type' => $reactionType,
                ]);

                $action       = 'ADDED';
                $finalReaction = $reactionType;
                $message      = "Reaction ('{$reactionType}') added to ordinance ID {$ordinanceId}.";
            } elseif ($existingReaction['reaction_type'] === $reactionType) {
                // State B — Identical reaction already exists: toggle it off (DELETE).
                $mutationStmt = $this->db->prepare("
                DELETE FROM Ordinance_Reactions
                WHERE  reaction_id = :reaction_id
            ");
                $mutationStmt->execute([
                    ':reaction_id' => $existingReaction['reaction_id'],
                ]);

                $action        = 'REMOVED';
                $finalReaction = null;
                $message       = "Reaction ('{$reactionType}') removed from ordinance ID {$ordinanceId}.";
            } else {
                // State C — Opposing reaction exists: UPDATE to the new type.
                // NOTE: Uses SET (not WHERE) — the original source contained a
                //       syntax defect here that would have thrown on every SWITCHED path.
                $mutationStmt = $this->db->prepare("
                UPDATE Ordinance_Reactions
                SET    reaction_type = :reaction_type,
                       updated_at    = NOW()
                WHERE  reaction_id   = :reaction_id
            ");
                $mutationStmt->execute([
                    ':reaction_type' => $reactionType,
                    ':reaction_id'   => $existingReaction['reaction_id'],
                ]);

                $action        = 'SWITCHED';
                $finalReaction = $reactionType;
                $message       = "Reaction switched to '{$reactionType}' on ordinance ID {$ordinanceId}.";
            }

            // ----------------------------------------------------------------
            // STEP 3 — Aggregate computation: executed WITHIN the open transaction
            // so that the counts reflect this session's uncommitted mutations.
            //
            // A single conditional-SUM pass avoids two separate COUNT queries and
            // remains index-friendly on (ordinance_id, reaction_type).
            //
            // If the platform is read-heavy and this query becomes a bottleneck,
            // consider a denormalization path: maintain a separate
            // `Ordinance_Reaction_Counts(ordinance_id, likes, dislikes)` table
            // updated via INSERT ... ON DUPLICATE KEY UPDATE in each branch above,
            // and replace this query with a single primary-key lookup on that table.
            // ----------------------------------------------------------------
            $aggregateStmt = $this->db->prepare("
            SELECT
                COALESCE(SUM(reaction_type = 'like'),    0) AS likes,
                COALESCE(SUM(reaction_type = 'dislike'), 0) AS dislikes
            FROM  Ordinance_Reactions
            WHERE ordinance_id = :ordinance_id
        ");

            $aggregateStmt->execute([':ordinance_id' => $ordinanceId]);
            $aggregates = $aggregateStmt->fetch(PDO::FETCH_ASSOC);

            // ----------------------------------------------------------------
            // STEP 4 — Commit: all mutations and reads are now atomic.
            // ----------------------------------------------------------------
            $this->db->commit();

            return [
                'status'  => 'success',
                'action'  => $action,
                'message' => $message,
                'data'    => [
                    'user_reaction' => $finalReaction,
                    'aggregates'    => [
                        'likes'    => (int) $aggregates['likes'],
                        'dislikes' => (int) $aggregates['dislikes'],
                    ],
                ],
            ];
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            // Delegate to the existing exception wrapper for MariaDB error-code
            // mapping and contextual message construction.
            return _handleDatabaseException($e, $ordinanceId, $reactionType);
        }
    }
}
