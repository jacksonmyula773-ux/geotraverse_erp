<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

require_once '../config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$department_id = isset($data['department_id']) ? intval($data['department_id']) : 0;
$current_dept = isset($_SESSION['department_id']) ? $_SESSION['department_id'] : 1;

if (!$department_id) {
    echo json_encode(['success' => false, 'message' => 'Department ID required']);
    exit;
}

$participant_1 = min($current_dept, $department_id);
$participant_2 = max($current_dept, $department_id);

$stmt = $conn->prepare("DELETE FROM conversations WHERE participant_1 = ? AND participant_2 = ?");
$stmt->bind_param("ii", $participant_1, $participant_2);
$stmt->execute();

echo json_encode(['success' => true]);
?>