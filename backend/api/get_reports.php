<?php
// backend/api/get_reports.php
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
$user_role = $_SESSION['role'];

if ($user_dept == 1 || $user_role == 'Super Administrator') {
    $query = "SELECT r.*, d.name as department_name 
              FROM reports r 
              LEFT JOIN departments d ON r.department_id = d.id 
              ORDER BY r.id DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
} else {
    $query = "SELECT r.*, d.name as department_name 
              FROM reports r 
              LEFT JOIN departments d ON r.department_id = d.id 
              WHERE r.department_id = ? 
              ORDER BY r.id DESC";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_dept]);
}

$reports = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $reports[] = $row;
}

echo json_encode([
    'success' => true,
    'count' => count($reports),
    'data' => $reports
]);
?>