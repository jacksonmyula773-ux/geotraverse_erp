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

$current_dept = isset($_SESSION['department_id']) ? $_SESSION['department_id'] : 1;

if ($current_dept == 1) {
    $stmt = $conn->prepare("SELECT d.*, dept.name as department_name FROM daily_work d LEFT JOIN departments dept ON d.department_id = dept.id ORDER BY d.date DESC");
    $stmt->execute();
} else {
    $stmt = $conn->prepare("SELECT d.*, dept.name as department_name FROM daily_work d LEFT JOIN departments dept ON d.department_id = dept.id WHERE d.department_id = ? ORDER BY d.date DESC");
    $stmt->bind_param("i", $current_dept);
    $stmt->execute();
}

$result = $stmt->get_result();
$dailywork = [];

while ($row = $result->fetch_assoc()) {
    $dailywork[] = $row;
}

echo json_encode(['success' => true, 'data' => $dailywork]);
?>