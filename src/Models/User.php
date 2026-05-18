<?php

namespace App\Models;

use PDO;
use PDOException;

class User
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
            }

            error_log("User::register() failure: " . $e->getMessage());

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
}