<?php
// backend/api/delete_message.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

error_log("=== DELETE MESSAGE API CALLED ===");

$inputJSON = file_get_contents("php://input");
error_log("Raw input: " . $inputJSON);

$data = json_decode($inputJSON, true);

if (!$data) {
    $data = $_POST;
}

error_log("Processed data: " . print_r($data, true));

if (!isset($data['message_id']) || empty($data['message_id'])) {
    sendResponse(false, null, "Message ID is required");
}

$messageId = intval($data['message_id']);

$database = new Database();
$db = $database->getConnection();

// Check if message exists and user has permission (admin can delete any message)
$checkQuery = "SELECT m.id, m.conversation_id, c.admin_id 
               FROM messages m
               JOIN conversations c ON m.conversation_id = c.id
               WHERE m.id = :msg_id";
$checkStmt = $db->prepare($checkQuery);
$checkStmt->bindParam(':msg_id', $messageId);
$checkStmt->execute();

if ($checkStmt->rowCount() === 0) {
    sendResponse(false, null, "Message not found");
}

$message = $checkStmt->fetch(PDO::FETCH_ASSOC);

// Only admin (admin_id = 1) can delete messages
if ($message['admin_id'] != 1) {
    sendResponse(false, null, "Permission denied");
}

// Delete the message
$query = "DELETE FROM messages WHERE id = :msg_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':msg_id', $messageId);

if ($stmt->execute()) {
    error_log("Message deleted successfully: " . $messageId);
    sendResponse(true, null, "Message deleted successfully");
} else {
    $error = $stmt->errorInfo();
    error_log("Delete message error: " . print_r($error, true));
    sendResponse(false, null, "Failed to delete message");
}
?>