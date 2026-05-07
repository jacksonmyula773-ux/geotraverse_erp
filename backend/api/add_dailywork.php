<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../includes/auth.php';
require_once '../config/database.php';

require_permission('add_daily_work');

$data = json_decode(file_get_contents('php://input'), true);

$required_fields = ['project_name', 'work_description'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => "$field is required"]);
        exit();
    }
}

$date = isset($data['date']) ? $data['date'] : date('Y-m-d');
$project_name = $conn->real_escape_string($data['project_name']);
$work_description = $conn->real_escape_string($data['work_description']);
$income = isset($data['income']) ? floatval($data['income']) : 0;
$expenses = isset($data['expenses']) ? floatval($data['expenses']) : 0;
$paid_amount = isset($data['paid_amount']) ? floatval($data['paid_amount']) : 0;
$profit = $income - $expenses;
$remaining = $income - $paid_amount;
$status = $paid_amount >= $income ? 'paid' : ($paid_amount > 0 ? 'partial' : 'pending');
$department_id = get_current_department_id();

$stmt = $conn->prepare("
    INSERT INTO daily_work (department_id, date, project_name, work_description, income, expenses, paid_amount, remaining, profit, status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("isssddddds", $department_id, $date, $project_name, $work_description, $income, $expenses, $paid_amount, $remaining, $profit, $status);

if ($stmt->execute()) {
    log_activity(get_current_user_id(), $department_id, "Added daily work for: $project_name");
    echo json_encode(['success' => true, 'message' => 'Daily work added successfully', 'id' => $conn->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add daily work: ' . $conn->error]);
}
?>