<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['message_id'])) {
    sendResponse(false, null, "Message ID required");
}

$database = new Database();
$db = $database->getConnection();

$query = "DELETE FROM messages WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $data['message_id']);

if ($stmt->execute()) {
    sendResponse(true, null, "Message deleted");
} else {
    sendResponse(false, null, "Failed to delete message");
}
?>