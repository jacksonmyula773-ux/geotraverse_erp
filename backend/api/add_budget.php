<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$category = $data['category'] ?? '';
$allocated_amount = $data['allocated_amount'] ?? 0;
$used_amount = $data['used_amount'] ?? 0;
$year = $data['year'] ?? date('Y');
$month = $data['month'] ?? date('n');
$department_id = $data['department_id'] ?? ($_SESSION['department_id'] ?? 2);
$description = $data['description'] ?? '';

if (!$category || !$allocated_amount) {
    echo json_encode(['success' => false, 'message' => 'Category and allocated amount required']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO budget_allocations (category, allocated_amount, used_amount, year, month, department_id, description, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("sddiiss", $category, $allocated_amount, $used_amount, $year, $month, $department_id, $description);
$stmt->execute();

echo json_encode(['success' => true, 'id' => $conn->insert_id]);
?>