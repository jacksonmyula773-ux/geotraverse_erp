<?php
// backend/api/get_messages.php
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

error_log("=== GET MESSAGES API CALLED ===");

$database = new Database();
$db = $database->getConnection();

// Get all conversations for admin (admin_id = 1) with active status
$query = "SELECT 
    c.id as conversation_id,
    c.user_id,
    c.admin_id,
    c.subject,
    c.status as conversation_status,
    c.created_at as conversation_created,
    c.updated_at,
    u.name as user_name,
    u.department_id,
    d.name as department_name,
    (SELECT m.message FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) as last_message,
    (SELECT m.created_at FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) as last_message_time,
    (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.receiver_id = 1 AND m.is_read = 0) as unread_count
FROM conversations c
JOIN users u ON c.user_id = u.id
JOIN departments d ON u.department_id = d.id
WHERE c.admin_id = 1 AND c.status = 'active'
ORDER BY c.updated_at DESC, last_message_time DESC";

$stmt = $db->prepare($query);
$stmt->execute();

$conversations = array();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $conversations[] = array(
        'conversation_id' => (int)$row['conversation_id'],
        'user_id' => (int)$row['user_id'],
        'department_id' => (int)$row['department_id'],
        'department_name' => $row['department_name'],
        'user_name' => $row['user_name'],
        'subject' => $row['subject'],
        'conversation_status' => $row['conversation_status'],
        'last_message' => $row['last_message'],
        'last_message_time' => $row['last_message_time'],
        'unread_count' => (int)$row['unread_count'],
        'updated_at' => $row['updated_at']
    );
}

error_log("Total conversations found: " . count($conversations));

sendResponse(true, $conversations);
?>