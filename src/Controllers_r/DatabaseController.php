<?php

namespace App\Controllers;

use PDO;
use PDOException;

class DatabaseController
{
    /**
     * Evaluates the runtime context and establishes a deterministic PDO instance.
     *
     * @return PDO An initialized and configured database connection layer.
     */
    public static function getDatabaseConnection(): PDO
    {
        // Define the structural signatures of known local environments
        $localHosts = ['localhost', '127.0.0.1', '[::1]'];

        // 1. Check for Command Line Interface execution
        $isCli = (php_sapi_name() === 'cli');

        // 2. Extract the clean hostname without port boundaries if running via Web Server
        $httpHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        $cleanHost = current(explode(':', $httpHost));

        // 3. Fallback tracking using local server IP addresses
        $serverAddr = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '';

        // 4. Synthesize local environmental truth
        $localHosts = ['localhost', '127.0.0.1', '[::1]'];
        $isLocal = $isCli || in_array($cleanHost, $localHosts) || in_array($serverAddr, $localHosts);

        if ($isLocal) {
            // Local Development Stack Configuration (e.g., XAMPP, MAMP, Docker)
            $host     = 'localhost';
            $dbName   = 'if0_41928864_encyclawphilia_db';
            $user     = 'root';
            $password = ''; // Default local state
        } else {
            // Production Server Configuration (InfinityFree)
            $host     = 'sql209.infinityfree.com';
            $dbName   = 'if0_41928864_encyclawphilia_db';
            $user     = 'if0_41928864';
            $password = '6WyumXiRAWG0'; // Retain actual production token securely
        }

        // Standardize driver attributes for consistency across environments
        $dsn = "mysql:host={$host};dbname={$dbName};charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            return new PDO($dsn, $user, $password, $options);
        } catch (PDOException $e) {
            // Restrict verbose debugging statements from outputting on production nodes
            if ($isLocal) {
                die("Local Database connection failure: " . $e->getMessage());
            } else {
                error_log("Production Database Failure: " . $e->getMessage());
                die("System Error: Unable to resolve data state. Please try again later.");
            }
        }
    }
}
