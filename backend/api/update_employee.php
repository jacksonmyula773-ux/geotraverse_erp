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

$id = $data['id'] ?? 0;
$name = $data['name'] ?? '';
$email = $data['email'] ?? '';
$phone = $data['phone'] ?? '';
$department_id = $data['department_id'] ?? 1;
$role = $data['role'] ?? 'Staff';
$salary = $data['salary'] ?? 0;
$password = isset($data['password']) && !empty($data['password']) ? md5($data['password']) : null;

if (!$id || !$name || !$email) {
    echo json_encode(['success' => false, 'message' => 'ID, name and email required']);
    exit;
}

if ($password) {
    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, department_id = ?, role = ?, salary = ?, password = ? WHERE id = ?");
    $stmt->bind_param("sssisisi", $name, $email, $phone, $department_id, $role, $salary, $password, $id);
} else {
    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, department_id = ?, role = ?, salary = ? WHERE id = ?");
    $stmt->bind_param("sssisii", $name, $email, $phone, $department_id, $role, $salary, $id);
}
$stmt->execute();

echo json_encode(['success' => true]);
?>