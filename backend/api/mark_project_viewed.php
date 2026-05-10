<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

require_once '../config/database.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$project_id = isset($data['project_id']) ? intval($data['project_id']) : 0;

if (!$project_id) {
    echo json_encode(['success' => false, 'message' => 'Project ID required']);
    exit;
}

$stmt = $conn->prepare("UPDATE projects SET is_viewed_by_admin = 1 WHERE id = ?");
$stmt->bind_param("i", $project_id);
$stmt->execute();

echo json_encode(['success' => true]);
?>