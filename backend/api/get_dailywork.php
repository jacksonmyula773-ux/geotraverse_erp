<?php
// backend/api/get_dailywork.php
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

// Build query based on user role
if ($user_dept == 1 || $user_role == 'Super Administrator') {
    // Super admin sees all daily work
    $query = "SELECT dw.*, d.name as department_name 
              FROM daily_work dw 
              LEFT JOIN departments d ON dw.department_id = d.id 
              ORDER BY dw.date DESC, dw.id DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
} else {
    // Other departments see only their daily work
    $query = "SELECT dw.*, d.name as department_name 
              FROM daily_work dw 
              LEFT JOIN departments d ON dw.department_id = d.id 
              WHERE dw.department_id = ? 
              ORDER BY dw.date DESC, dw.id DESC";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_dept]);
}

$dailywork = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Calculate profit if not stored
    if (!isset($row['profit'])) {
        $row['profit'] = $row['income'] - $row['expenses'];
    }
    $dailywork[] = $row;
}

echo json_encode([
    'success' => true,
    'count' => count($dailywork),
    'data' => $dailywork
]);
?>