<?php
// backend/api/update_project.php
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

$checkQuery = "SELECT department_id FROM projects WHERE id = ?";
$checkStmt = $db->prepare($checkQuery);
$checkStmt->execute([$id]);
$project = $checkStmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    echo json_encode(['success' => false, 'message' => 'Project not found']);
    exit();
}

if ($user_dept != 1 && $user_role != 'Super Administrator' && $project['department_id'] != $user_dept) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$updates = [];
$params = [];

if (isset($data->name)) { $updates[] = "name = ?"; $params[] = $data->name; }
if (isset($data->client_name)) { $updates[] = "client_name = ?"; $params[] = $data->client_name; }
if (isset($data->amount)) { $updates[] = "amount = ?"; $params[] = $data->amount; }
if (isset($data->location)) { $updates[] = "location = ?"; $params[] = $data->location; }
if (isset($data->description)) { $updates[] = "description = ?"; $params[] = $data->description; }
if (isset($data->status)) { $updates[] = "status = ?"; $params[] = $data->status; }
if (isset($data->progress)) { $updates[] = "progress = ?"; $params[] = $data->progress; }
if (isset($data->start_date)) { $updates[] = "start_date = ?"; $params[] = $data->start_date; }
if (isset($data->end_date)) { $updates[] = "end_date = ?"; $params[] = $data->end_date; }

if (empty($updates)) {
    echo json_encode(['success' => false, 'message' => 'No fields to update']);
    exit();
}

$params[] = $id;
$query = "UPDATE projects SET " . implode(", ", $updates) . " WHERE id = ?";
$stmt = $db->prepare($query);

if ($stmt->execute($params)) {
    echo json_encode(['success' => true, 'message' => 'Project updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update project']);
}
?>