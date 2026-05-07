<?php
// backend/api/send_message.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit();
}

if (empty($data->to_department_id)) {
    echo json_encode(['success' => false, 'message' => 'Recipient department is required']);
    exit();
}

if (empty($data->message)) {
    echo json_encode(['success' => false, 'message' => 'Message content is required']);
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$from_dept = $_SESSION['department_id'];
$to_dept = (int)$data->to_department_id;
$message = $data->message;

$query = "INSERT INTO messages (from_department_id, to_department_id, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())";
$stmt = $db->prepare($query);

if ($stmt->execute([$from_dept, $to_dept, $message])) {
    echo json_encode(['success' => true, 'message' => 'Message sent successfully', 'message_id' => $db->lastInsertId()]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send message']);
}
?>