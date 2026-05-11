<?php
// backend/api/send_message.php
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['to_department_id']) || !isset($data['message']) || empty($data['message'])) {
    sendResponse(false, null, "Department and message required");
}

$database = new Database();
$db = $database->getConnection();

$sender_dept = 1; // Admin
$receiver_dept = $data['to_department_id'];
$message = $data['message'];
$status = 'sent';

$query = "INSERT INTO messages (sender_dept, receiver_dept, message, status) 
          VALUES (:sender_dept, :receiver_dept, :message, :status)";
$stmt = $db->prepare($query);
$stmt->bindParam(':sender_dept', $sender_dept);
$stmt->bindParam(':receiver_dept', $receiver_dept);
$stmt->bindParam(':message', $message);
$stmt->bindParam(':status', $status);

if ($stmt->execute()) {
    sendResponse(true, ['id' => $db->lastInsertId()], "Message sent successfully");
} else {
    sendResponse(false, null, "Failed to send message");
}
?>