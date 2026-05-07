<?php
// backend/api/send_project.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'));

if (!$data || empty($data->project_id) || empty($data->to_department_id)) {
    echo json_encode(['success' => false, 'message' => 'Project ID and destination department required']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$project_id = (int)$data->project_id;
$to_dept = (int)$data->to_department_id;
$message = $data->message ?? '';
$from_dept = $_SESSION['department_id'];

// Get project details
$projQuery = "SELECT name, amount, status FROM projects WHERE id = ?";
$projStmt = $db->prepare($projQuery);
$projStmt->execute([$project_id]);
$project = $projStmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    echo json_encode(['success' => false, 'message' => 'Project not found']);
    exit();
}

// Create message content
$fullMessage = "PROJECT SHARED:\n";
$fullMessage .= "Project: " . $project['name'] . "\n";
$fullMessage .= "Amount: " . number_format($project['amount'], 2) . "\n";
$fullMessage .= "Status: " . $project['status'] . "\n";
if ($message) {
    $fullMessage .= "\nMessage from sender: " . $message;
}

// Insert message
$msgQuery = "INSERT INTO messages (from_department_id, to_department_id, message, created_at) VALUES (?, ?, ?, NOW())";
$msgStmt = $db->prepare($msgQuery);

if ($msgStmt->execute([$from_dept, $to_dept, $fullMessage])) {
    echo json_encode(['success' => true, 'message' => 'Project sent successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send project']);
}
?>