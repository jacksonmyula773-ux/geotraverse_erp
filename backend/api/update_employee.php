<?php
// backend/api/update_employee.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT');

// Only Super Admin, Manager, Secretary, and own employee can update
if (!isset($_SESSION['user_id'])) {
    echo '{"success":false,"message":"Not logged in"}';
    exit();
}

require_once '../config/database.php';

$rawData = file_get_contents('php://input');
$data = json_decode($rawData);

if (!$data) {
    echo '{"success":false,"message":"Invalid JSON data"}';
    exit();
}

if (empty($data->id)) {
    echo '{"success":false,"message":"Employee ID is required"}';
    exit();
}

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo '{"success":false,"message":"Database connection failed"}';
    exit();
}

$id = (int)$data->id;
$current_user_id = $_SESSION['user_id'];
$current_dept = $_SESSION['department_id'];
$current_role = $_SESSION['role'];

// Check if employee exists and get their department
$checkStmt = $db->prepare("SELECT department_id, name FROM users WHERE id = ? AND is_active = 1");
$checkStmt->execute([$id]);
$targetEmployee = $checkStmt->fetch(PDO::FETCH_ASSOC);

if (!$targetEmployee) {
    echo '{"success":false,"message":"Employee not found"}';
    exit();
}

$target_dept = $targetEmployee['department_id'];

// Permission logic
$canUpdate = false;

if ($current_dept == 1 || $current_role == 'Super Administrator') {
    $canUpdate = true; // Admin can update anyone
} elseif ($current_dept == 4 && $target_dept == $current_dept) {
    $canUpdate = true; // Manager can update own department only
} elseif ($current_dept == 5 && $target_dept == $current_dept) {
    $canUpdate = true; // Secretary can update own department only
} elseif ($current_user_id == $id) {
    $canUpdate = true; // User can update own profile (limited fields)
} else {
    $canUpdate = false;
}

if (!$canUpdate) {
    echo '{"success":false,"message":"You do not have permission to update this employee"}';
    exit();
}

// Build update query based on permissions
$updates = [];
$params = [];

// Everyone can update name
if (isset($data->name) && $data->name !== '') {
    $updates[] = "name = ?";
    $params[] = $data->name;
}

// Email check - only admin can change email
if (isset($data->email) && $data->email !== '') {
    if ($current_dept != 1 && $current_role != 'Super Administrator') {
        echo '{"success":false,"message":"Only admin can change email"}';
        exit();
    }
    
    $emailCheck = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $emailCheck->execute([$data->email, $id]);
    if ($emailCheck->rowCount() > 0) {
        echo '{"success":false,"message":"Email already exists for another employee"}';
        exit();
    }
    $updates[] = "email = ?";
    $params[] = $data->email;
}

// Phone - anyone can update own phone
if (isset($data->phone)) {
    $updates[] = "phone = ?";
    $params[] = $data->phone;
}

// Department - only admin can change department
if (isset($data->department_id)) {
    if ($current_dept != 1 && $current_role != 'Super Administrator') {
        echo '{"success":false,"message":"Only admin can change department"}';
        exit();
    }
    $updates[] = "department_id = ?";
    $params[] = $data->department_id ? (int)$data->department_id : null;
}

// Role - only admin/manager can change role
if (isset($data->role)) {
    if ($current_dept != 1 && $current_dept != 4) {
        echo '{"success":false,"message":"Only admin or manager can change role"}';
        exit();
    }
    $updates[] = "role = ?";
    $params[] = $data->role;
}

// Salary - only admin can change salary
if (isset($data->salary) && $data->salary !== '') {
    if ($current_dept != 1 && $current_role != 'Super Administrator') {
        echo '{"success":false,"message":"Only admin can change salary"}';
        exit();
    }
    $updates[] = "salary = ?";
    $params[] = (float)$data->salary;
}

// Password - user can change own password
if (isset($data->password) && $data->password !== '') {
    $updates[] = "password = ?";
    $params[] = md5($data->password);
}

if (empty($updates)) {
    echo '{"success":false,"message":"No fields to update"}';
    exit();
}

$params[] = $id;
$query = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
$stmt = $db->prepare($query);

if ($stmt->execute($params)) {
    $selectStmt = $db->prepare("SELECT id, name, email, phone, role, department_id FROM users WHERE id = ?");
    $selectStmt->execute([$id]);
    $employee = $selectStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Employee updated successfully',
        'data' => $employee
    ]);
} else {
    echo '{"success":false,"message":"Failed to update employee"}';
}
?>