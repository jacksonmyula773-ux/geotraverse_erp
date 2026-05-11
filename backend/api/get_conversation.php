<?php
// backend/api/get_conversation.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

error_log("=== GET CONVERSATION API CALLED ===");

$conversation_id = isset($_GET['conversation_id']) ? intval($_GET['conversation_id']) : 0;
$conversation_id = isset($_GET['id']) ? intval($_GET['id']) : $conversation_id;

error_log("Conversation ID: " . $conversation_id);

if (!$conversation_id) {
    sendResponse(false, null, "Conversation ID is required");
}

$database = new Database();
$db = $database->getConnection();

// Get conversation info with department details
$convQuery = "SELECT 
    c.id as conversation_id,
    c.user_id,
    c.admin_id,
    c.subject,
    c.status,
    c.created_at,
    c.updated_at,
    u.name as user_name,
    u.department_id,
    d.name as department_name
FROM conversations c
JOIN users u ON c.user_id = u.id
JOIN departments d ON u.department_id = d.id
WHERE c.id = :conv_id AND c.status = 'active'";

$convStmt = $db->prepare($convQuery);
$convStmt->bindParam(':conv_id', $conversation_id);
$convStmt->execute();

if ($convStmt->rowCount() === 0) {
    error_log("Conversation not found: " . $conversation_id);
    sendResponse(false, null, "Conversation not found");
}

$conversation = $convStmt->fetch(PDO::FETCH_ASSOC);
error_log("Conversation found: " . print_r($conversation, true));

// Mark unread messages as read (where receiver is admin)
$updateQuery = "UPDATE messages SET is_read = 1, read_at = NOW() 
                WHERE conversation_id = :conv_id AND receiver_id = 1 AND is_read = 0";
$updateStmt = $db->prepare($updateQuery);
$updateStmt->bindParam(':conv_id', $conversation_id);
$updateStmt->execute();
error_log("Marked read: " . $updateStmt->rowCount() . " messages");

// Get all messages in this conversation
$query = "SELECT 
    m.id,
    m.conversation_id,
    m.sender_id,
    m.receiver_id,
    m.message,
    m.is_read,
    m.read_at,
    m.status as message_status,
    m.created_at,
    CASE 
        WHEN m.sender_id = 1 THEN 'admin'
        ELSE 'department'
    END as sender_type,
    CASE 
        WHEN m.sender_id = 1 THEN 'Admin'
        ELSE (SELECT name FROM users WHERE id = m.sender_id)
    END as sender_name
FROM messages m
WHERE m.conversation_id = :conv_id
ORDER BY m.created_at ASC";

$stmt = $db->prepare($query);
$stmt->bindParam(':conv_id', $conversation_id);
$stmt->execute();

$messages = array();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $messages[] = array(
        'id' => (int)$row['id'],
        'conversation_id' => (int)$row['conversation_id'],
        'message' => $row['message'],
        'sender_id' => (int)$row['sender_id'],
        'receiver_id' => (int)$row['receiver_id'],
        'sender_type' => $row['sender_type'],
        'sender_name' => $row['sender_name'],
        'is_read' => (int)$row['is_read'],
        'status' => $row['message_status'],
        'created_at' => $row['created_at']
    );
}

error_log("Found " . count($messages) . " messages for conversation {$conversation_id}");

sendResponse(true, array(
    'conversation_id' => (int)$conversation['conversation_id'],
    'user_id' => (int)$conversation['user_id'],
    'department_id' => (int)$conversation['department_id'],
    'department_name' => $conversation['department_name'],
    'user_name' => $conversation['user_name'],
    'subject' => $conversation['subject'],
    'messages' => $messages
));
?>