<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

require_once '../config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$current_dept = isset($_SESSION['department_id']) ? $_SESSION['department_id'] : 1;

if ($current_dept == 1) {
    $stmt = $conn->prepare("SELECT r.*, d.name as department_name FROM reports r LEFT JOIN departments d ON r.department_id = d.id ORDER BY r.created_at DESC");
    $stmt->execute();
} else {
    $stmt = $conn->prepare("SELECT r.*, d.name as department_name FROM reports r LEFT JOIN departments d ON r.department_id = d.id WHERE r.department_id = ? ORDER BY r.created_at DESC");
    $stmt->bind_param("i", $current_dept);
    $stmt->execute();
}

$result = $stmt->get_result();
$reports = [];

while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
}

// Count unviewed reports for admin
$unviewed_count = 0;
if ($current_dept == 1) {
    $unviewedStmt = $conn->prepare("SELECT COUNT(*) as count FROM reports WHERE is_viewed_by_admin = 0 AND department_id != 1");
    $unviewedStmt->execute();
    $unviewedResult = $unviewedStmt->get_result();
    $unviewed_count = $unviewedResult->fetch_assoc()['count'];
}

echo json_encode([
    'success' => true, 
    'data' => $reports,
    'unviewed_count' => $unviewed_count
]);
?>