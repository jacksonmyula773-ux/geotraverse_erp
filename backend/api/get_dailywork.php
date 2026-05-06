<?php
/**
 * Get Daily Work Records
 * Method: GET
 * Parameters: department_id (optional), date (optional), status (optional)
 */

require_once '../config/database.php';
require_once '../includes/auth.php';

$auth = new Auth();
$user = $auth->validateToken();

if (!$user) {
    sendResponse(false, null, "Unauthorized", 401);
}

$database = new Database();
$db = $database->getConnection();

$department_id = isset($_GET['department_id']) ? $_GET['department_id'] : $user['department_id'];
$date = isset($_GET['date']) ? $_GET['date'] : null;
$status = isset($_GET['status']) ? $_GET['status'] : null;

$query = "SELECT dw.*, d.name as department_name 
          FROM daily_work dw 
          LEFT JOIN departments d ON dw.department_id = d.id 
          WHERE 1=1";

$params = [];

if ($department_id) {
    $query .= " AND dw.department_id = :dept_id";
    $params[':dept_id'] = $department_id;
}

if ($date) {
    $query .= " AND dw.date = :date";
    $params[':date'] = $date;
}

if ($status) {
    $query .= " AND dw.status = :status";
    $params[':status'] = $status;
}

$query .= " ORDER BY dw.date DESC, dw.id DESC";

$stmt = $db->prepare($query);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->execute();
$dailyWorks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$totalIncome = 0;
$totalExpenses = 0;
$totalProfit = 0;
$totalPaid = 0;
$totalRemaining = 0;

foreach ($dailyWorks as &$dw) {
    $totalIncome += $dw['income'];
    $totalExpenses += $dw['expenses'];
    $profit = $dw['income'] - $dw['expenses'];
    $dw['profit'] = $profit;
    $totalProfit += $profit;
    $totalPaid += $dw['paid_amount'];
    $totalRemaining += $dw['remaining'];
}

sendResponse(true, [
    'records' => $dailyWorks,
    'summary' => [
        'total_income' => $totalIncome,
        'total_expenses' => $totalExpenses,
        'total_profit' => $totalProfit,
        'total_paid' => $totalPaid,
        'total_remaining' => $totalRemaining,
        'count' => count($dailyWorks)
    ]
]);
?>