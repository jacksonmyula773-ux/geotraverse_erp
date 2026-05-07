<?php
// backend/api/send_message.php
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

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

if (empty($data->to_department_id) || empty($data->message)) {
    echo json_encode(['success' => false, 'message' => 'Recipient and message required']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$from_dept = $_SESSION['department_id'];
$to_dept = (int)$data->to_department_id;
$message = $data->message;
$is_read = 0;

$query = "INSERT INTO messages (from_department_id, to_department_id, message, is_read, created_at) VALUES (?, ?, ?, ?, NOW())";
$stmt = $db->prepare($query);

if ($stmt->execute([$from_dept, $to_dept, $message, $is_read])) {
    echo json_encode([
        'success' => true,
        'message' => 'Message sent successfully',
        'data' => ['id' => $db->lastInsertId()]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send message']);
}
?>