<?php
// backend/api/send_message.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'));

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

if (empty($data->to_department_id) || empty($data->message)) {
    echo json_encode(['success' => false, 'message' => 'Department and message required']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$from_dept = 1; // Super Admin department ID
$to_dept = (int)$data->to_department_id;
$message = $data->message;

$query = "INSERT INTO messages (from_department_id, to_department_id, message, created_at) VALUES (?, ?, ?, NOW())";
$stmt = $db->prepare($query);

if ($stmt->execute([$from_dept, $to_dept, $message])) {
    echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send message']);
}
?>