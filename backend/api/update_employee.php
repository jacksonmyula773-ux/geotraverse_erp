<?php
// backend/api/update_employee.php
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'])) {
    sendResponse(false, null, "Employee ID required");
}

$database = new Database();
$db = $database->getConnection();

$fields = [];
$params = [':id' => $data['id']];

if (isset($data['name'])) {
    $fields[] = "name = :name";
    $params[':name'] = $data['name'];
}
if (isset($data['email'])) {
    $fields[] = "email = :email";
    $params[':email'] = $data['email'];
}
if (isset($data['phone'])) {
    $fields[] = "phone = :phone";
    $params[':phone'] = $data['phone'];
}
if (isset($data['department_id'])) {
    $fields[] = "department_id = :department_id";
    $params[':department_id'] = $data['department_id'];
}
if (isset($data['role'])) {
    $fields[] = "role = :role";
    $params[':role'] = $data['role'];
}
if (isset($data['salary'])) {
    $fields[] = "salary = :salary";
    $params[':salary'] = $data['salary'];
}
if (isset($data['password']) && !empty($data['password'])) {
    $fields[] = "password = :password";
    $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
}

if (empty($fields)) {
    sendResponse(false, null, "No fields to update");
}

$query = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = :id";
$stmt = $db->prepare($query);

if ($stmt->execute($params)) {
    sendResponse(true, null, "Employee updated successfully");
} else {
    sendResponse(false, null, "Failed to update employee");
}
?>