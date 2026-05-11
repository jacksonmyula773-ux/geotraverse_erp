<?php
// backend/api/get_conversation.php
require_once '../config/database.php';

$department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;

if (!$department_id) {
    sendResponse(false, null, "Department ID required");
}

$database = new Database();
$db = $database->getConnection();

// Get department name
$deptQuery = "SELECT name FROM departments WHERE id = :id";
$deptStmt = $db->prepare($deptQuery);
$deptStmt->bindParam(':id', $department_id);
$deptStmt->execute();
$department = $deptStmt->fetch(PDO::FETCH_ASSOC);

// Mark messages as read
$updateQuery = "UPDATE messages SET is_read = 1, read_at = NOW() WHERE receiver_dept = 1 AND sender_dept = :dept_id AND is_read = 0";
$updateStmt = $db->prepare($updateQuery);
$updateStmt->bindParam(':dept_id', $department_id);
$updateStmt->execute();

// Get messages
$query = "SELECT m.*,
    CASE WHEN m.sender_dept = 1 THEN 'admin' ELSE 'department' END as sender_type
    FROM messages m
    WHERE (m.sender_dept = 1 AND m.receiver_dept = :dept_id) OR (m.sender_dept = :dept_id AND m.receiver_dept = 1)
    ORDER BY m.created_at ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(':dept_id', $department_id);
$stmt->execute();

$messages = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $messages[] = $row;
}

sendResponse(true, [
    'messages' => $messages,
    'department_name' => $department ? $department['name'] : ''
]);
?>