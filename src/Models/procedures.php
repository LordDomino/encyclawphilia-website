<?php

namespace App\Models;

use PDO;
use PDOException;

/**
 * Translates PDO Exceptions into application level responses based on MariaDB error codes.
 */
function _handleDatabaseException(PDOException $e, int $ordinanceId, int $reactionTypeId): array
{
    // Error code 1452 indicates a Foreign Key constraint failure in MariaDB
    if ($e->errorInfo[1] === 1452) {
        $errorContext = $e->getMessage();

        if (str_contains($errorContext, 'ordinance_id')) {
            return ['action' => 'ERROR', 'message' => 'ERROR: Ordinance not found or is inaccessible.'];
        }
        if (str_contains($errorContext, 'user_id')) {
            return ['action' => 'ERROR', 'message' => 'ERROR: User not found or account is deactivated.'];
        }
        if (str_contains($errorContext, 'reaction_type_id')) {
            return ['action' => 'ERROR', 'message' => "ERROR: Invalid reaction_type_id ({$reactionTypeId})."];
        }
    }

    // Default structural fallback for unexpected database exceptions
    return [
        'action'  => 'ERROR',
        'message' => 'ERROR: Transaction failed due to an internal database error.'
    ];
}

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

// /**
//  * Executes user registration logic previously handled by sp_UserSignUp.
//  *
//  * @param PDO    $pdo          Authenticated PDO instance.
//  * @param string $username     Raw name string.
//  * @param string $email        Raw email address string.
//  * @param string $passwordHash Pre-hashed password string.
//  * @param int    $roleId       Target role identifier.
//  * @return array Contains 'user_id' (int) and 'message' (string).
//  */
// function registerUser(PDO $pdo, string $username, string $email, string $passwordHash, int $roleId): array
// {
//     // Replicating SQL local variable initialization & data transformation
//     $trimmedName  = trim($username);
//     $trimmedEmail = strtolower(trim($email));
//     $cleanPassword = trim($passwordHash);

//     // Structural Validation Phase (Guard Clauses)
//     if ($trimmedName === '') {
//         return ['user_id' => 0, 'message' => 'ERROR: Full name is required.'];
//     }

//     if ($trimmedEmail === '') {
//         return ['user_id' => 0, 'message' => 'ERROR: Email is required.'];
//     }

//     if ($cleanPassword === '') {
//         return ['user_id' => 0, 'message' => 'ERROR: Password is required.'];
//     }

//     try {
//         // Begin transaction block to isolate analytical reads and subsequent write operations
//         $pdo->beginTransaction();

//         // 4. Execution Phase: Mutating Schema State
//         $insertStmt = $pdo->prepare("
//             INSERT INTO Users (username, email, password_hash, role_id)
//             VALUES (:username, :email, :password_hash, :role_id)
//         ");
        
//         $insertStmt->execute([
//             ':username'      => $trimmedName,
//             ':email'         => $trimmedEmail,
//             ':password_hash' => $passwordHash, // Retaining original hash structure
//             ':role_id'       => $roleId
//         ]);

//         // Capture state details before confirming data persistence
//         $newUserId = (int)$pdo->lastInsertId();

//         // Commit transaction blocks updates to disk atomically
//         $pdo->commit();

//         return [
//             'user_id' => $newUserId,
//             'message' => "SUCCESS: User registered with ID {$newUserId}."
//         ];

//     } catch (PDOException $e) {
//         // Safeguard against partial executions or engine failures
//         if ($pdo->inTransaction()) {
//             $pdo->rollBack();
//         }
        
//         // Log $e->getMessage() privately for engineering forensics
//         return [
//             'user_id' => 0,
//             'message' => 'ERROR: System failure encountered during compilation or persistence.'
//         ];
//     }
// }

// /**
//  * Validates user credentials against stored relational identities.
//  *
//  * @param PDO    $pdo      Active connection instance.
//  * @param string $email    Raw client-supplied identification string.
//  * @param string $password Raw client-supplied credential string.
//  * @return array           An associative array denoting outcome status and user context.
//  */
// function verifyUserLogin(PDO $pdo, string $email, string $password): array
// {
//     $sql = "
//         SELECT 
//             user_id, 
//             username, 
//             password_hash, 
//             role_id 
//         FROM Users 
//         WHERE email = :email 
//           AND deleted_at IS NULL 
//         LIMIT 1;
//     ";

//     try {
//         $stmt = $pdo->prepare($sql);
//         $stmt->execute([':email' => strtolower(trim($email))]);
//         $user = $stmt->fetch(PDO::FETCH_ASSOC);

//         // Terminate early if the identification string does not match any records
//         if (!$user) {
//             return ['authenticated' => false, 'message' => 'ERROR: Invalid credentials.'];
//         }

//         // Cryptographic evaluation of the plaintext input against the storage hash
//         if (!password_verify($password, $user['password_hash'])) {
//             return ['authenticated' => false, 'message' => 'ERROR: Invalid credentials.'];
//         }

//         // Return a safe subset of the identity payload on successful matching
//         return [
//             'authenticated' => true,
//             'message'       => 'SUCCESS: Authentication verified.',
//             'user'          => [
//                 'id'        => (int)$user['user_id'],
//                 'username' => $user['username'],
//                 'role_id'   => (int)$user['role_id']
//             ]
//         ];

//     } catch (PDOException $e) {
//         error_log("Authentication routine system fault: " . $e->getMessage());
//         return ['authenticated' => false, 'message' => 'ERROR: System infrastructure failure.'];
//     }
// }