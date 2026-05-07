<?php
// backend/api/get_unread_count.php
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

$query = "SELECT COUNT(*) as unread_count FROM messages WHERE to_department_id = ? AND is_read = 0";
$stmt = $db->prepare($query);
$stmt->execute([$user_dept]);

$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'unread_count' => (int)$result['unread_count']
]);
?>