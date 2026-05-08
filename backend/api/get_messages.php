<?php
require_once 'db_connection.php';

// Get all messages for Super Admin with grouping by department
$query = "SELECT m.*, 
          u1.name as sender_name, u1.department_id as sender_dept,
          u2.name as receiver_name, u2.department_id as receiver_dept,
          d.name as department_name
          FROM messages m
          JOIN users u1 ON m.sender_id = u1.id
          JOIN users u2 ON m.receiver_id = u2.id
          LEFT JOIN departments d ON (u1.department_id = d.id OR u2.department_id = d.id)
          WHERE u1.department_id = 1 OR u2.department_id = 1
          ORDER BY m.created_at DESC";

$result = $conn->query($query);
$messages = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Get the other department
        $otherDept = $row['sender_dept'] == 1 ? $row['receiver_dept'] : $row['sender_dept'];
        $row['other_department_id'] = $otherDept;
        $messages[] = $row;
    }
}

// Count unread messages
$unreadQuery = "SELECT COUNT(*) as unread FROM messages m 
                JOIN users u ON m.receiver_id = u.id 
                WHERE u.department_id = 1 AND m.is_read = 0";
$unreadResult = $conn->query($unreadQuery);
$unreadCount = $unreadResult ? $unreadResult->fetch_assoc()['unread'] : 0;

echo json_encode(['success' => true, 'data' => $messages, 'unread_count' => $unreadCount]);

$conn->close();
?>