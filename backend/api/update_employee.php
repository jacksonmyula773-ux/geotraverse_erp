<?php
// backend/api/update_employee.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'));

if (!$data || empty($data->id)) {
    echo json_encode(['success' => false, 'message' => 'Employee ID required']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$id = (int)$data->id;

// Check if employee exists
$checkStmt = $db->prepare("SELECT id FROM users WHERE id = ? AND is_active = 1");
$checkStmt->execute([$id]);

if ($checkStmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'message' => 'Employee not found']);
    exit();
}

// Build update query
$updates = [];
$params = [];

if (isset($data->name) && !empty($data->name)) {
    $updates[] = "name = ?";
    $params[] = $data->name;
}
if (isset($data->email) && !empty($data->email)) {
    $updates[] = "email = ?";
    $params[] = $data->email;
}
if (isset($data->phone)) {
    $updates[] = "phone = ?";
    $params[] = $data->phone;
}
if (isset($data->department_id)) {
    $updates[] = "department_id = ?";
    $params[] = (int)$data->department_id;
}
if (isset($data->role) && !empty($data->role)) {
    $updates[] = "role = ?";
    $params[] = $data->role;
}
if (isset($data->salary)) {
    $updates[] = "salary = ?";
    $params[] = (float)$data->salary;
}
if (isset($data->password) && !empty($data->password)) {
    $updates[] = "password = ?";
    $params[] = md5($data->password);
}

if (empty($updates)) {
    echo json_encode(['success' => false, 'message' => 'No fields to update']);
    exit();
}

$params[] = $id;
$query = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
$stmt = $db->prepare($query);

if ($stmt->execute($params)) {
    echo json_encode(['success' => true, 'message' => 'Employee updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update employee']);
}
?>