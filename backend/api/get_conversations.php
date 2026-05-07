<?php
// backend/api/get_conversation.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

require_once '../config/database.php';

$other_dept = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;

if ($other_dept <= 0) {
    echo json_encode(['success' => false, 'message' => 'Department ID required']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$user_dept = $_SESSION['department_id'];

$query = "SELECT m.*, 
          d1.name as from_department_name,
          d2.name as to_department_name
          FROM messages m
          LEFT JOIN departments d1 ON m.from_department_id = d1.id
          LEFT JOIN departments d2 ON m.to_department_id = d2.id
          WHERE (m.from_department_id = ? AND m.to_department_id = ?) 
             OR (m.from_department_id = ? AND m.to_department_id = ?)
          ORDER BY m.created_at ASC";

$stmt = $db->prepare($query);
$stmt->execute([$user_dept, $other_dept, $other_dept, $user_dept]);

$messages = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $messages[] = $row;
}

// Mark messages as read
$updateQuery = "UPDATE messages SET is_read = 1 WHERE from_department_id = ? AND to_department_id = ? AND is_read = 0";
$updateStmt = $db->prepare($updateQuery);
$updateStmt->execute([$other_dept, $user_dept]);

echo json_encode([
    'success' => true,
    'count' => count($messages),
    'data' => $messages
]);
?>