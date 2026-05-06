<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$project_id = $data['project_id'] ?? 0;
$to_department_id = $data['department_id'] ?? 0;
$message = $data['message'] ?? '';

if (!$project_id || !$to_department_id) {
    echo json_encode(['success' => false, 'error' => 'Project ID and Department ID required']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get project details
$query = "SELECT * FROM projects WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $project_id);
$stmt->execute();
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    echo json_encode(['success' => false, 'error' => 'Project not found']);
    exit();
}

$fullMessage = "PROJECT SHARED FROM ADMIN:\n";
$fullMessage .= "Name: " . $project['name'] . "\n";
$fullMessage .= "Client: " . $project['client_name'] . "\n";
$fullMessage .= "Amount: " . number_format($project['amount'], 0) . " TZS\n";
$fullMessage .= "Status: " . $project['status'] . "\n";
$fullMessage .= "Progress: " . $project['progress'] . "%\n";
$fullMessage .= "Location: " . ($project['location'] ?? 'N/A') . "\n";
$fullMessage .= "Description: " . ($project['description'] ?? 'N/A') . "\n";
if ($message) {
    $fullMessage .= "\nMessage from Admin:\n" . $message;
}

$query = "INSERT INTO messages (from_department_id, to_department_id, message, is_read) 
          VALUES (1, :to_dept, :message, 0)";

$stmt = $db->prepare($query);
$stmt->bindParam(':to_dept', $to_department_id);
$stmt->bindParam(':message', $fullMessage);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to send project']);
}
?>