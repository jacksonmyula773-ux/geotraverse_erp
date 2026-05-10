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

$date = $data['date'] ?? date('Y-m-d');
$project_name = $data['project_name'] ?? '';
$work_description = $data['work_description'] ?? '';
$income = $data['income'] ?? 0;
$expenses = $data['expenses'] ?? 0;
$paid_amount = $data['paid_amount'] ?? 0;
$status = $data['status'] ?? 'pending';
$department_id = $data['department_id'] ?? ($_SESSION['department_id'] ?? 1);

if (!$project_name) {
    echo json_encode(['success' => false, 'message' => 'Project name required']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO daily_work (date, project_name, work_description, income, expenses, paid_amount, status, department_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("sssdddsi", $date, $project_name, $work_description, $income, $expenses, $paid_amount, $status, $department_id);
$stmt->execute();

echo json_encode(['success' => true, 'id' => $conn->insert_id]);
?>