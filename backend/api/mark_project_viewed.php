<?php
require_once 'db_connection.php';

$data = json_decode(file_get_contents('php://input'), true);
$messageId = isset($data['message_id']) ? intval($data['message_id']) : null;

if (!$messageId) {
    echo json_encode(['success' => false, 'message' => 'Message ID required']);
    exit;
}

$query = "UPDATE messages SET is_read = 1, read_at = NOW() WHERE id = $messageId";

if ($conn->query($query)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to mark message as read: ' . $conn->error]);
}

$conn->close();
?>