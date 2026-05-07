<?php
// backend/api/delete_conversation.php
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

if (!$data || empty($data->department_id)) {
    echo json_encode(['success' => false, 'message' => 'Department ID required']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$other_dept = (int)$data->department_id;
$user_dept = $_SESSION['department_id'];

// Delete all messages between user and the other department
$query = "DELETE FROM messages WHERE (from_department_id = ? AND to_department_id = ?) OR (from_department_id = ? AND to_department_id = ?)";
$stmt = $db->prepare($query);

if ($stmt->execute([$user_dept, $other_dept, $other_dept, $user_dept])) {
    $deleted_count = $stmt->rowCount();
    echo json_encode([
        'success' => true, 
        'message' => "Conversation deleted successfully ($deleted_count messages removed)"
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete conversation']);
}
?>