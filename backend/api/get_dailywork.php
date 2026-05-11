<?php
// backend/api/get_dailywork.php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$query = "SELECT dw.*, d.name as department_name 
          FROM daily_work dw
          LEFT JOIN departments d ON dw.department_id = d.id
          ORDER BY dw.date DESC, dw.id DESC";
$stmt = $db->prepare($query);
$stmt->execute();

$dailywork = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $dailywork[] = $row;
}

sendResponse(true, $dailywork);
?>