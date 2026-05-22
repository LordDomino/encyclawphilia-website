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