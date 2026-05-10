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
$date = $data['date'] ?? date('Y-m-d');
$project_name = $data['project_name'] ?? '';
$work_description = $data['work_description'] ?? '';
$income = $data['income'] ?? 0;
$expenses = $data['expenses'] ?? 0;
$paid_amount = $data['paid_amount'] ?? 0;
$status = $data['status'] ?? 'pending';
$department_id = $data['department_id'] ?? 1;

if (!$id || !$project_name) {
    echo json_encode(['success' => false, 'message' => 'ID and project name required']);
    exit;
}

$stmt = $conn->prepare("UPDATE daily_work SET date = ?, project_name = ?, work_description = ?, income = ?, expenses = ?, paid_amount = ?, status = ?, department_id = ? WHERE id = ?");
$stmt->bind_param("sssdddsii", $date, $project_name, $work_description, $income, $expenses, $paid_amount, $status, $department_id, $id);
$stmt->execute();

echo json_encode(['success' => true]);
?>