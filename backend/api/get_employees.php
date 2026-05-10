<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

require_once '../config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// For super admin, get all employees, otherwise get department employees
$current_dept = isset($_SESSION['department_id']) ? $_SESSION['department_id'] : 1;

if ($current_dept == 1) {
    $stmt = $conn->prepare("SELECT u.*, d.name as department_name FROM users u LEFT JOIN departments d ON u.department_id = d.id ORDER BY u.id DESC");
    $stmt->execute();
} else {
    $stmt = $conn->prepare("SELECT u.*, d.name as department_name FROM users u LEFT JOIN departments d ON u.department_id = d.id WHERE u.department_id = ? ORDER BY u.id DESC");
    $stmt->bind_param("i", $current_dept);
    $stmt->execute();
}

$result = $stmt->get_result();
$employees = [];

while ($row = $result->fetch_assoc()) {
    // Remove password for security
    unset($row['password']);
    $employees[] = $row;
}

echo json_encode(['success' => true, 'data' => $employees]);
?>