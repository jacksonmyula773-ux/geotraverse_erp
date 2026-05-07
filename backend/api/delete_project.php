<?php
// backend/api/delete_project.php
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

if (!$data || empty($data->id)) {
    echo json_encode(['success' => false, 'message' => 'Project ID required']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$id = (int)$data->id;
$user_dept = $_SESSION['department_id'];
$user_role = $_SESSION['role'];

// Check permission (only admin can delete)
if ($user_dept != 1 && $user_role != 'Super Administrator') {
    echo json_encode(['success' => false, 'message' => 'Only admin can delete projects']);
    exit();
}

$stmt = $db->prepare("DELETE FROM projects WHERE id = ?");

if ($stmt->execute([$id])) {
    echo json_encode(['success' => true, 'message' => 'Project deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete project']);
}
?>