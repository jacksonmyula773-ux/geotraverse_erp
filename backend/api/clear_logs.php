<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$query = "TRUNCATE TABLE activity_logs";
$stmt = $db->prepare($query);

if ($stmt->execute()) {
    // Add new log
    $action = "Activity logs cleared by admin";
    $userId = $_SESSION['user_id'] ?? null;
    $insertQuery = "INSERT INTO activity_logs (user_id, action) VALUES (:user_id, :action)";
    $insertStmt = $db->prepare($insertQuery);
    $insertStmt->bindParam(':user_id', $userId);
    $insertStmt->bindParam(':action', $action);
    $insertStmt->execute();
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to clear logs']);
}
?>