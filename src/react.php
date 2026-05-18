<?php

require_once '../config/database.php';
require_once 'procedures.php';
// 1. Start session to check authentication
session_start();

// 2. Set response header to JSON
header('Content-Type: application/json');

// 3. Verify user authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

// 4. Validate the incoming POST data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reaction_type'])) {
    $ordinanceId = $_POST['ordinance_id'];
    $userId = $_SESSION['user_id'];
    $reaction = $_POST['reaction_type'];

    $pdo = getDatabaseConnection();
    $success = reactToOrdinance($pdo, $ordinanceId, $userId, $reaction);
    // $success = true; // Temporary placeholder

    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
exit;