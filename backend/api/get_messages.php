<?php
// backend/api/get_messages.php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get conversations for admin (department_id = 1)
$query = "SELECT 
    d.id as other_dept_id,
    d.name as other_dept_name,
    (SELECT message FROM messages WHERE (sender_dept = d.id AND receiver_dept = 1) OR (sender_dept = 1 AND receiver_dept = d.id) ORDER BY created_at DESC LIMIT 1) as last_message,
    (SELECT created_at FROM messages WHERE (sender_dept = d.id AND receiver_dept = 1) OR (sender_dept = 1 AND receiver_dept = d.id) ORDER BY created_at DESC LIMIT 1) as last_message_time,
    (SELECT COUNT(*) FROM messages WHERE receiver_dept = 1 AND sender_dept = d.id AND is_read = 0) as unread_count
    FROM departments d
    WHERE d.id != 1
    AND EXISTS (SELECT 1 FROM messages WHERE (sender_dept = d.id AND receiver_dept = 1) OR (sender_dept = 1 AND receiver_dept = d.id))
    ORDER BY last_message_time DESC";
$stmt = $db->prepare($query);
$stmt->execute();

$conversations = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $conversations[] = $row;
}

sendResponse(true, $conversations);
?>