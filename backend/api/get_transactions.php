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
    $stmt = $conn->prepare("SELECT t.*, d.name as department_name FROM transactions t LEFT JOIN departments d ON t.department_id = d.id ORDER BY t.transaction_date DESC");
    $stmt->execute();
} else {
    $stmt = $conn->prepare("SELECT t.*, d.name as department_name FROM transactions t LEFT JOIN departments d ON t.department_id = d.id WHERE t.department_id = ? ORDER BY t.transaction_date DESC");
    $stmt->bind_param("i", $current_dept);
    $stmt->execute();
}

$result = $stmt->get_result();
$transactions = [];

while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}

echo json_encode(['success' => true, 'data' => $transactions]);
?>