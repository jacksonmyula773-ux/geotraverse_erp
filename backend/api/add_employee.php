<?php
// backend/api/add_employee.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

// Only Super Admin, Manager, or Secretary can add employees
$allowed_departments = [1, 4, 5]; // Admin, Manager, Secretary

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['department_id'], $allowed_departments)) {
    echo '{"success":false,"message":"You dont have permission to add employees"}';
    exit();
}

require_once '../config/database.php';

$rawData = file_get_contents('php://input');
$data = json_decode($rawData);

if (!$data) {
    echo '{"success":false,"message":"Invalid JSON data"}';
    exit();
}

// Validate required fields
if (empty($data->name)) {
    echo '{"success":false,"message":"Name is required"}';
    exit();
}

if (empty($data->email)) {
    echo '{"success":false,"message":"Email is required"}';
    exit();
}

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo '{"success":false,"message":"Database connection failed"}';
    exit();
}

// Check if email exists
$checkStmt = $db->prepare("SELECT id FROM users WHERE email = ?");
$checkStmt->execute([$data->email]);

if ($checkStmt->rowCount() > 0) {
    echo '{"success":false,"message":"Email already exists"}';
    exit();
}

// Get current user's department for default
$current_dept = $_SESSION['department_id'];

// If not admin, force employee to be added to current department
if ($current_dept != 1 && isset($data->department_id) && $data->department_id != $current_dept) {
    echo '{"success":false,"message":"You can only add employees to your own department"}';
    exit();
}

// Prepare data
$name = $data->name;
$email = $data->email;
$phone = $data->phone ?? '';
$department_id = ($current_dept == 1) ? ($data->department_id ?? null) : $current_dept;
$role = $data->role ?? 'Staff';
$salary = $data->salary ?? 0;
$password = md5($data->password ?? '1234');
$join_date = $data->join_date ?? date('Y-m-d');

// Insert
$query = "INSERT INTO users (name, email, phone, department_id, role, salary, password, join_date, is_active) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";

$stmt = $db->prepare($query);

if ($stmt->execute([$name, $email, $phone, $department_id, $role, $salary, $password, $join_date])) {
    $newId = $db->lastInsertId();
    
    $selectStmt = $db->prepare("SELECT id, name, email, phone, role, department_id FROM users WHERE id = ?");
    $selectStmt->execute([$newId]);
    $newEmployee = $selectStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Employee added successfully',
        'data' => $newEmployee
    ]);
} else {
    echo '{"success":false,"message":"Failed to add employee"}';
}
?>