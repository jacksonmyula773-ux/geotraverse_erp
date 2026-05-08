<?php
require_once 'db_connection.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit;
}

$toDepartmentId = isset($data['to_department_id']) ? intval($data['to_department_id']) : null;
$message = isset($data['message']) ? trim($data['message']) : '';

if (!$toDepartmentId || !$message) {
    echo json_encode(['success' => false, 'message' => 'Department and message required']);
    exit;
}

// Get Super Admin user
$adminQuery = "SELECT id FROM users WHERE department_id = 1 LIMIT 1";
$adminResult = $conn->query($adminQuery);
$admin = $adminResult->fetch_assoc();

if (!$admin) {
    echo json_encode(['success' => false, 'message' => 'Super Admin user not found']);
    exit;
}

// Get target department user
$targetQuery = "SELECT id FROM users WHERE department_id = $toDepartmentId LIMIT 1";
$targetResult = $conn->query($targetQuery);
$target = $targetResult->fetch_assoc();

if (!$target) {
    echo json_encode(['success' => false, 'message' => 'Department user not found']);
    exit;
}

// Find or create conversation
$convQuery = "SELECT id FROM conversations WHERE (user_id = {$admin['id']} AND admin_id = {$target['id']}) OR (user_id = {$target['id']} AND admin_id = {$admin['id']})";
$convResult = $conn->query($convQuery);
$conversationId = null;

if ($convResult && $convResult->num_rows > 0) {
    $conv = $convResult->fetch_assoc();
    $conversationId = $conv['id'];
} else {
    $insertConv = "INSERT INTO conversations (user_id, admin_id, subject, status, created_at) VALUES ({$admin['id']}, {$target['id']}, 'Chat', 'active', NOW())";
    if ($conn->query($insertConv)) {
        $conversationId = $conn->insert_id;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create conversation']);
        exit;
    }
}

// Insert message
$escapedMessage = mysqli_real_escape_string($conn, $message);
$insertMsg = "INSERT INTO messages (conversation_id, sender_id, receiver_id, message, status, created_at, is_read) 
              VALUES ($conversationId, {$admin['id']}, {$target['id']}, '$escapedMessage', 'sent', NOW(), 0)";

if ($conn->query($insertMsg)) {
    echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send message: ' . $conn->error]);
}

$conn->close();
?>