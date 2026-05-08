<?php
require_once 'db_connection.php';

$departmentId = isset($_GET['department_id']) ? intval($_GET['department_id']) : null;

if (!$departmentId) {
    echo json_encode(['success' => false, 'message' => 'Department ID required', 'data' => []]);
    exit;
}

// Get department name
$deptQuery = "SELECT name FROM departments WHERE id = $departmentId";
$deptResult = $conn->query($deptQuery);
$deptName = $deptResult->fetch_assoc();
$departmentName = $deptName ? $deptName['name'] : 'Department';

// Get Super Admin user
$adminQuery = "SELECT id FROM users WHERE department_id = 1 LIMIT 1";
$adminResult = $conn->query($adminQuery);
$admin = $adminResult->fetch_assoc();

if (!$admin) {
    echo json_encode(['success' => false, 'message' => 'Super Admin not found', 'data' => [], 'department_name' => $departmentName]);
    exit;
}

// Get target department user
$targetQuery = "SELECT id FROM users WHERE department_id = $departmentId LIMIT 1";
$targetResult = $conn->query($targetQuery);
$target = $targetResult->fetch_assoc();

if (!$target) {
    echo json_encode(['success' => false, 'message' => 'Department user not found', 'data' => [], 'department_name' => $departmentName]);
    exit;
}

// Get messages between Super Admin and department
$query = "SELECT m.*, 
          u1.name as sender_name, u1.department_id as sender_dept,
          u2.name as receiver_name, u2.department_id as receiver_dept
          FROM messages m
          JOIN users u1 ON m.sender_id = u1.id
          JOIN users u2 ON m.receiver_id = u2.id
          WHERE (m.sender_id = {$admin['id']} AND m.receiver_id = {$target['id']}) 
             OR (m.sender_id = {$target['id']} AND m.receiver_id = {$admin['id']})
          ORDER BY m.created_at ASC";

$result = $conn->query($query);
$messages = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
}

echo json_encode(['success' => true, 'data' => $messages, 'department_name' => $departmentName]);

$conn->close();
?>