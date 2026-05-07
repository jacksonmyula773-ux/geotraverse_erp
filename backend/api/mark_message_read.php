<?php
// backend/api/mark_message_read.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'));

if (!$data || empty($data->message_id)) {
    echo json_encode(['success' => false, 'message' => 'Message ID required']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$message_id = (int)$data->message_id;
$user_dept = $_SESSION['department_id'];

// Only mark as read if user is the recipient
$query = "UPDATE messages SET is_read = 1 WHERE id = ? AND to_department_id = ?";
$stmt = $db->prepare($query);

if ($stmt->execute([$message_id, $user_dept])) {
    echo json_encode(['success' => true, 'message' => 'Message marked as read']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to mark message']);
}
?>