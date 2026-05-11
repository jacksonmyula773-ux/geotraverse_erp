<?php
// backend/api/add_dailywork.php
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['project_name']) || empty($data['project_name'])) {
    sendResponse(false, null, "Project name required");
}

$database = new Database();
$db = $database->getConnection();

$date = $data['date'] ?? date('Y-m-d');
$project_name = $data['project_name'];
$work_description = $data['work_description'] ?? null;
$income = $data['income'] ?? 0;
$expenses = $data['expenses'] ?? 0;
$paid_amount = $data['paid_amount'] ?? 0;
$status = $data['status'] ?? 'pending';
$department_id = $data['department_id'] ?? 1;

$query = "INSERT INTO daily_work (date, project_name, work_description, income, expenses, paid_amount, status, department_id) 
          VALUES (:date, :project_name, :work_description, :income, :expenses, :paid_amount, :status, :department_id)";
$stmt = $db->prepare($query);
$stmt->bindParam(':date', $date);
$stmt->bindParam(':project_name', $project_name);
$stmt->bindParam(':work_description', $work_description);
$stmt->bindParam(':income', $income);
$stmt->bindParam(':expenses', $expenses);
$stmt->bindParam(':paid_amount', $paid_amount);
$stmt->bindParam(':status', $status);
$stmt->bindParam(':department_id', $department_id);

if ($stmt->execute()) {
    sendResponse(true, ['id' => $db->lastInsertId()], "Daily work added successfully");
} else {
    sendResponse(false, null, "Failed to add daily work");
}
?>