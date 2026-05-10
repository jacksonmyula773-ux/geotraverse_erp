<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

require_once '../config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$name = $data['name'] ?? '';
$email = $data['email'] ?? '';
$phone = $data['phone'] ?? '';
$department_id = $data['department_id'] ?? 1;
$role = $data['role'] ?? 'Staff';
$salary = $data['salary'] ?? 0;
$password = isset($data['password']) ? md5($data['password']) : md5('1234');

if (!$name || !$email) {
    echo json_encode(['success' => false, 'message' => 'Name and email required']);
    exit;
}

// Check if email exists
$checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$checkStmt->bind_param("s", $email);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already exists']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO users (name, email, phone, department_id, role, salary, password, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("sssisis", $name, $email, $phone, $department_id, $role, $salary, $password);
$stmt->execute();

echo json_encode(['success' => true, 'id' => $conn->insert_id]);
?>