<?php
// backend/api/get_departments.php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$query = "SELECT id, name, email, phone, description FROM departments WHERE id != 1 ORDER BY id";
$stmt = $db->prepare($query);
$stmt->execute();

$departments = array();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $departments[] = array(
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'email' => $row['email'],
        'phone' => $row['phone'],
        'description' => $row['description']
    );
}

// Also get department users for messaging
$userQuery = "SELECT u.id, u.name, u.department_id, d.name as department_name
              FROM users u
              JOIN departments d ON u.department_id = d.id
              WHERE u.department_id != 1 AND u.is_active = 1
              ORDER BY d.id, u.name";
$userStmt = $db->prepare($userQuery);
$userStmt->execute();
$users = array();
while ($row = $userStmt->fetch(PDO::FETCH_ASSOC)) {
    $users[] = array(
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'department_id' => (int)$row['department_id'],
        'department_name' => $row['department_name']
    );
}

sendResponse(true, array(
    'departments' => $departments,
    'users' => $users
));
?>