<?php
// backend/api/get_messages.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

require_once '../config/database.php';

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
          WHERE m.from_department_id = ? OR m.to_department_id = ?
          ORDER BY m.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute([$user_dept, $user_dept]);

$messages = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Set default values if columns don't exist
    if (!isset($row['is_read'])) $row['is_read'] = 0;
    if (!isset($row['is_delivered'])) $row['is_delivered'] = 1;
    $messages[] = $row;
}

$unreadCount = 0;
if ($user_dept == 1) {
    $unreadQuery = "SELECT COUNT(*) as count FROM messages WHERE to_department_id = 1 AND is_read = 0";
    $unreadStmt = $db->prepare($unreadQuery);
    $unreadStmt->execute();
    $unreadCount = $unreadStmt->fetch(PDO::FETCH_ASSOC)['count'];
}

echo json_encode([
    'success' => true,
    'count' => count($messages),
    'unread_count' => $unreadCount,
    'data' => $messages
]);
?>