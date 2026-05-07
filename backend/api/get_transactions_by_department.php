<?php
// backend/api/get_transactions_by_department.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

require_once '../config/database.php';

$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;
$type = isset($_GET['type']) ? $_GET['type'] : null;

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$query = "SELECT t.*, d.name as department_name 
          FROM transactions t 
          LEFT JOIN departments d ON t.department_id = d.id 
          WHERE 1=1";
$params = [];

if ($department_id > 0) {
    $query .= " AND t.department_id = ?";
    $params[] = $department_id;
}

if ($start_date) {
    $query .= " AND t.transaction_date >= ?";
    $params[] = $start_date;
}

if ($end_date) {
    $query .= " AND t.transaction_date <= ?";
    $params[] = $end_date;
}

if ($type) {
    $query .= " AND t.type = ?";
    $params[] = $type;
}

$query .= " ORDER BY t.transaction_date DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);

$transactions = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $transactions[] = $row;
}

// Calculate summary
$total_income = array_sum(array_filter($transactions, function($t) { return $t['type'] == 'income'; }));
$total_expenses = array_sum(array_filter($transactions, function($t) { return $t['type'] == 'expense'; }));
$total_paid = array_sum(array_filter($transactions, function($t) { return $t['status'] == 'paid'; }));

echo json_encode([
    'success' => true,
    'count' => count($transactions),
    'summary' => [
        'total_income' => $total_income,
        'total_expenses' => $total_expenses,
        'net_profit' => $total_income - $total_expenses,
        'total_paid' => $total_paid
    ],
    'data' => $transactions
]);
?>