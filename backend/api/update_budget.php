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

$id = $data['id'] ?? 0;
$category = $data['category'] ?? '';
$allocated_amount = $data['allocated_amount'] ?? 0;
$used_amount = $data['used_amount'] ?? 0;
$year = $data['year'] ?? date('Y');
$month = $data['month'] ?? date('n');
$description = $data['description'] ?? '';

if (!$id || !$category) {
    echo json_encode(['success' => false, 'message' => 'ID and category required']);
    exit;
}

$stmt = $conn->prepare("UPDATE budget_allocations SET category = ?, allocated_amount = ?, used_amount = ?, year = ?, month = ?, description = ? WHERE id = ?");
$stmt->bind_param("sddiisi", $category, $allocated_amount, $used_amount, $year, $month, $description, $id);
$stmt->execute();

echo json_encode(['success' => true]);
?>