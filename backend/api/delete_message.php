<?php
// backend/api/delete_message.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, POST');

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

// Check if user is sender or receiver (both can delete their own messages)
$checkQuery = "SELECT id FROM messages WHERE id = ? AND (from_department_id = ? OR to_department_id = ?)";
$checkStmt = $db->prepare($checkQuery);
$checkStmt->execute([$message_id, $user_dept, $user_dept]);

if ($checkStmt->rowCount() == 0) {
    echo json_encode(['success' => false, 'message' => 'Message not found or access denied']);
    exit();
}

$stmt = $db->prepare("DELETE FROM messages WHERE id = ?");

if ($stmt->execute([$message_id])) {
    echo json_encode(['success' => true, 'message' => 'Message deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete message']);
}
?>