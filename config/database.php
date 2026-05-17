<?php
// database.php
function getDatabaseConnection() {
    try {
        return new PDO('mysql:host=sql209.infinityfree.com;dbname=if0_41928864_encyclawphilia_db', 'if0_41928864', '6WyumXiRAWG0');
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}