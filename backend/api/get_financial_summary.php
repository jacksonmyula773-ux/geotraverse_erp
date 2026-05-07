<?php
// backend/api/get_financial_summary.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$user_dept = $_SESSION['department_id'];
$user_role = $_SESSION['role'];

// Get monthly summary
$monthlyQuery = "SELECT 
                    YEAR(transaction_date) as year,
                    MONTH(transaction_date) as month,
                    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
                    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expenses
                 FROM transactions";
                 
if ($user_dept != 1 && $user_role != 'Super Administrator') {
    $monthlyQuery .= " WHERE department_id = " . $user_dept;
}

$monthlyQuery .= " GROUP BY YEAR(transaction_date), MONTH(transaction_date) 
                   ORDER BY year DESC, month DESC LIMIT 12";

$monthlyStmt = $db->prepare($monthlyQuery);
$monthlyStmt->execute();
$monthlyData = $monthlyStmt->fetchAll(PDO::FETCH_ASSOC);

// Get summary by department (admin only)
$deptSummary = [];
if ($user_dept == 1 || $user_role == 'Super Administrator') {
    $deptQuery = "SELECT d.name as department_name,
                         SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END) as total_income,
                         SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END) as total_expenses
                  FROM departments d
                  LEFT JOIN transactions t ON d.id = t.department_id
                  WHERE d.id != 1
                  GROUP BY d.id";
    $deptStmt = $db->prepare($deptQuery);
    $deptStmt->execute();
    $deptSummary = $deptStmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode([
    'success' => true,
    'monthly_summary' => $monthlyData,
    'department_summary' => $deptSummary
]);
?>