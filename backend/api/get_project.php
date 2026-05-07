<?php
// backend/api/get_project.php
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
    echo json_encode(['success' => false, 'message' => 'Invalid project ID']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$query = "SELECT p.*, d.name as department_name 
          FROM projects p 
          LEFT JOIN departments d ON p.department_id = d.id 
          WHERE p.id = ?";

$stmt = $db->prepare($query);
$stmt->execute([$id]);

if ($stmt->rowCount() > 0) {
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check permission
    $user_dept = $_SESSION['department_id'];
    $user_role = $_SESSION['role'];
    
    if ($user_dept != 1 && $user_role != 'Super Administrator' && $project['department_id'] != $user_dept) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit();
    }
    
    echo json_encode(['success' => true, 'data' => $project]);
} else {
    echo json_encode(['success' => false, 'message' => 'Project not found']);
}
?>