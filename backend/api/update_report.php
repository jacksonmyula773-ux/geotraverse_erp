<?php
// backend/api/update_report.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'));

if (!$data || empty($data->id)) {
    echo json_encode(['success' => false, 'message' => 'Report ID required']);
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

// Check ownership
$checkQuery = "SELECT department_id FROM reports WHERE id = ?";
$checkStmt = $db->prepare($checkQuery);
$checkStmt->execute([$id]);
$report = $checkStmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    echo json_encode(['success' => false, 'message' => 'Report not found']);
    exit();
}

if ($report['department_id'] != $user_dept && $_SESSION['department_id'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$updates = [];
$params = [];

if (isset($data->title)) {
    $updates[] = "title = ?";
    $params[] = $data->title;
}
if (isset($data->period)) {
    $updates[] = "period = ?";
    $params[] = $data->period;
}
if (isset($data->content)) {
    $updates[] = "content = ?";
    $params[] = $data->content;
}
if (isset($data->status)) {
    $updates[] = "status = ?";
    $params[] = $data->status;
}

if (empty($updates)) {
    echo json_encode(['success' => false, 'message' => 'No fields to update']);
    exit();
}

$params[] = $id;
$query = "UPDATE reports SET " . implode(", ", $updates) . " WHERE id = ?";
$stmt = $db->prepare($query);

if ($stmt->execute($params)) {
    echo json_encode(['success' => true, 'message' => 'Report updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update report']);
}
?>