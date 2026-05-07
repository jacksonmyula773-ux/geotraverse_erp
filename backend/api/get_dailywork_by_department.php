<?php
// backend/api/get_dailywork_by_department.php
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

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$query = "SELECT dw.*, d.name as department_name 
          FROM daily_work dw 
          LEFT JOIN departments d ON dw.department_id = d.id 
          WHERE 1=1";
$params = [];

if ($department_id > 0) {
    $query .= " AND dw.department_id = ?";
    $params[] = $department_id;
}

if ($start_date) {
    $query .= " AND dw.date >= ?";
    $params[] = $start_date;
}

if ($end_date) {
    $query .= " AND dw.date <= ?";
    $params[] = $end_date;
}

$query .= " ORDER BY dw.date DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);

$dailywork = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $row['profit'] = $row['income'] - $row['expenses'];
    $dailywork[] = $row;
}

// Calculate summary
$total_income = array_sum(array_column($dailywork, 'income'));
$total_expenses = array_sum(array_column($dailywork, 'expenses'));
$total_profit = $total_income - $total_expenses;
$total_pending = array_sum(array_filter(array_column($dailywork, 'remaining'), function($r) { return $r > 0; }));

echo json_encode([
    'success' => true,
    'count' => count($dailywork),
    'summary' => [
        'total_income' => $total_income,
        'total_expenses' => $total_expenses,
        'total_profit' => $total_profit,
        'total_pending' => $total_pending
    ],
    'data' => $dailywork
]);
?>