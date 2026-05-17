<?php
// database.php
function getDatabaseConnection() {
    try {
        return new PDO('mysql:host=localhost;dbname=encyclawphilia_db', 'root', '');
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}