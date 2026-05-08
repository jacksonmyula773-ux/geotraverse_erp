<?php
require_once 'db_connection.php';

$data = json_decode(file_get_contents('php://input'), true);
$messageId = isset($data['message_id']) ? intval($data['message_id']) : null;

if (!$messageId) {
    echo json_encode(['success' => false, 'message' => 'Message ID required']);
    exit;
}

// First check if message exists
$checkQuery = "SELECT id FROM messages WHERE id = $messageId";
$checkResult = $conn->query($checkQuery);

if ($checkResult && $checkResult->num_rows > 0) {
    $query = "DELETE FROM messages WHERE id = $messageId";
    if ($conn->query($query)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete message: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Message not found']);
}

$conn->close();
?>