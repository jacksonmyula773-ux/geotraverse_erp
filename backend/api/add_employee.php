<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

// For Super Admin, we don't need to check department
// Just accept any request

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->name) || !isset($data->email)) {
    echo json_encode(['success' => false, 'error' => 'Name and email required']);
    exit();
}

$passwordHash = password_hash($data->password ?? '1234', PASSWORD_DEFAULT);
$joinDate = date('Y-m-d');

$query = "INSERT INTO users (name, email, phone, department_id, role, salary, password, status, join_date, created_at) 
          VALUES (:name, :email, :phone, :dept_id, :role, :salary, :password, 'Active', :join_date, NOW())";

$stmt = $db->prepare($query);
$stmt->bindParam(':name', $data->name);
$stmt->bindParam(':email', $data->email);
$stmt->bindParam(':phone', $data->phone);
$stmt->bindParam(':dept_id', $data->department_id);
$stmt->bindParam(':role', $data->role);
$stmt->bindParam(':salary', $data->salary);
$stmt->bindParam(':password', $passwordHash);
$stmt->bindParam(':join_date', $joinDate);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'data' => ['id' => $db->lastInsertId()]]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to add employee']);
}
?>