<?php
// backend/api/mark_project_viewed.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

if (!isset($_SESSION['user_id']) || ($_SESSION['department_id'] != 1 && $_SESSION['role'] != 'Super Administrator')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'));

if (!$data || empty($data->project_id)) {
    echo json_encode(['success' => false, 'message' => 'Project ID required']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$project_id = (int)$data->project_id;

$query = "UPDATE projects SET is_viewed_by_admin = 1 WHERE id = ?";
$stmt = $db->prepare($query);

if ($stmt->execute([$project_id])) {
    echo json_encode(['success' => true, 'message' => 'Project marked as viewed']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update']);
}
?>