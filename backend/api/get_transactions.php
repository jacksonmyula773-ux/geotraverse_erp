<?php
// backend/api/get_transactions.php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$query = "SELECT t.*, d.name as department_name 
          FROM transactions t
          LEFT JOIN departments d ON t.department_id = d.id
          ORDER BY t.transaction_date DESC, t.id DESC";
$stmt = $db->prepare($query);
$stmt->execute();

$transactions = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $transactions[] = $row;
}

sendResponse(true, $transactions);
?>