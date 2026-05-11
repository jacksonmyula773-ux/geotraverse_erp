<?php
// backend/api/get_dailywork.php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$query = "SELECT 
    dw.*,
    d.name as department_name
FROM daily_work dw
LEFT JOIN departments d ON dw.department_id = d.id
ORDER BY dw.date DESC, dw.id DESC";

$stmt = $db->prepare($query);
$stmt->execute();

$dailywork = array();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $dailywork[] = array(
        'id' => (int)$row['id'],
        'date' => $row['date'],
        'project_name' => $row['project_name'],
        'work_description' => $row['work_description'],
        'income' => floatval($row['income']),
        'expenses' => floatval($row['expenses']),
        'paid_amount' => floatval($row['paid_amount']),
        'status' => $row['status'],
        'department_id' => (int)$row['department_id'],
        'department_name' => $row['department_name'],
        'created_at' => $row['created_at']
    );
}

sendResponse(true, $dailywork);
?>