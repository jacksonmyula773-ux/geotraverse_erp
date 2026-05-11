<?php
// backend/api/add_employee.php
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['name']) || !isset($data['email'])) {
    sendResponse(false, null, "Name and email required");
}

$database = new Database();
$db = $database->getConnection();

// Check if email exists
$check = "SELECT id FROM users WHERE email = :email";
$stmt = $db->prepare($check);
$stmt->bindParam(':email', $data['email']);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    sendResponse(false, null, "Email already exists");
}

$password = isset($data['password']) && !empty($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : password_hash("123456", PASSWORD_DEFAULT);
$name = $data['name'];
$email = $data['email'];
$phone = $data['phone'] ?? null;
$department_id = $data['department_id'] ?? 1;
$role = $data['role'] ?? 'Staff';
$salary = $data['salary'] ?? 0;
$join_date = $data['join_date'] ?? date('Y-m-d');

$query = "INSERT INTO users (name, email, password, phone, department_id, role, salary, join_date) 
          VALUES (:name, :email, :password, :phone, :department_id, :role, :salary, :join_date)";
$stmt = $db->prepare($query);
$stmt->bindParam(':name', $name);
$stmt->bindParam(':email', $email);
$stmt->bindParam(':password', $password);
$stmt->bindParam(':phone', $phone);
$stmt->bindParam(':department_id', $department_id);
$stmt->bindParam(':role', $role);
$stmt->bindParam(':salary', $salary);
$stmt->bindParam(':join_date', $join_date);

if ($stmt->execute()) {
    sendResponse(true, ['id' => $db->lastInsertId()], "Employee added successfully");
} else {
    sendResponse(false, null, "Failed to add employee");
}
?>