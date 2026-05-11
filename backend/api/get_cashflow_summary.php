<?php
// backend/api/get_cashflow_summary.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get monthly income and expenses
$query = "SELECT 
            DATE_FORMAT(transaction_date, '%Y-%m') as month,
            SUM(CASE WHEN type = 'income' AND status = 'paid' THEN amount ELSE 0 END) as total_income,
            SUM(CASE WHEN type = 'expense' AND status = 'paid' THEN amount ELSE 0 END) as total_expenses
          FROM transactions 
          WHERE transaction_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
          GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
          ORDER BY month DESC";

$stmt = $db->prepare($query);
$stmt->execute();

$monthlyData = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $monthlyData[] = [
        'month' => $row['month'],
        'income' => floatval($row['total_income']),
        'expenses' => floatval($row['total_expenses']),
        'profit' => floatval($row['total_income']) - floatval($row['total_expenses'])
    ];
}

// Get totals
$queryTotals = "SELECT 
                  SUM(CASE WHEN type = 'income' AND status = 'paid' THEN amount ELSE 0 END) as total_income,
                  SUM(CASE WHEN type = 'expense' AND status = 'paid' THEN amount ELSE 0 END) as total_expenses
                FROM transactions";

$stmtTotals = $db->prepare($queryTotals);
$stmtTotals->execute();
$totals = $stmtTotals->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'data' => [
        'summary' => [
            'total_income' => floatval($totals['total_income']),
            'total_expenses' => floatval($totals['total_expenses']),
            'net_profit' => floatval($totals['total_income']) - floatval($totals['total_expenses'])
        ],
        'monthly_data' => $monthlyData
    ]
]);