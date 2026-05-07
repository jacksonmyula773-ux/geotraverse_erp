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

// Check if column exists
$checkColumn = $db->query("SHOW COLUMNS FROM reports LIKE 'is_viewed_by_admin'");
$hasViewedColumn = $checkColumn->rowCount() > 0;

if ($user_dept == 1 || $user_role == 'Super Administrator') {
    if ($hasViewedColumn) {
        $query = "SELECT r.*, d.name as department_name 
                  FROM reports r 
                  LEFT JOIN departments d ON r.department_id = d.id 
                  ORDER BY r.id DESC";
    } else {
        $query = "SELECT r.*, d.name as department_name, 0 as is_viewed_by_admin
                  FROM reports r 
                  LEFT JOIN departments d ON r.department_id = d.id 
                  ORDER BY r.id DESC";
    }
    $stmt = $db->prepare($query);
    $stmt->execute();
} else {
    if ($hasViewedColumn) {
        $query = "SELECT r.*, d.name as department_name 
                  FROM reports r 
                  LEFT JOIN departments d ON r.department_id = d.id 
                  WHERE r.department_id = ? 
                  ORDER BY r.id DESC";
    } else {
        $query = "SELECT r.*, d.name as department_name, 0 as is_viewed_by_admin
                  FROM reports r 
                  LEFT JOIN departments d ON r.department_id = d.id 
                  WHERE r.department_id = ? 
                  ORDER BY r.id DESC";
    }
    $stmt = $db->prepare($query);
    $stmt->execute([$user_dept]);
}

$reports = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (!isset($row['is_viewed_by_admin'])) {
        $row['is_viewed_by_admin'] = 0;
    }
    $reports[] = $row;
}

$unviewedCount = 0;
if ($hasViewedColumn && ($user_dept == 1 || $user_role == 'Super Administrator')) {
    $unviewedQuery = "SELECT COUNT(*) as count FROM reports WHERE is_viewed_by_admin = 0 AND department_id != 1";
    $unviewedStmt = $db->prepare($unviewedQuery);
    $unviewedStmt->execute();
    $unviewedCount = $unviewedStmt->fetch(PDO::FETCH_ASSOC)['count'];
}

echo json_encode([
    'success' => true,
    'count' => count($reports),
    'unviewed_count' => $unviewedCount,
    'data' => $reports
]);
?>