<?php
// backend/api/get_dailywork_by_id.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

require_once '../config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid daily work ID']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$user_dept = $_SESSION['department_id'];
$user_role = $_SESSION['role'];

$query = "SELECT dw.*, d.name as department_name 
          FROM daily_work dw 
          LEFT JOIN departments d ON dw.department_id = d.id 
          WHERE dw.id = ?";

$stmt = $db->prepare($query);
$stmt->execute([$id]);

if ($stmt->rowCount() > 0) {
    $dailywork = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check permission
    if ($user_dept != 1 && $user_role != 'Super Administrator' && $dailywork['department_id'] != $user_dept) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit();
    }
    
    $dailywork['profit'] = $dailywork['income'] - $dailywork['expenses'];
    
    echo json_encode(['success' => true, 'data' => $dailywork]);
} else {
    echo json_encode(['success' => false, 'message' => 'Daily work record not found']);
}
?>