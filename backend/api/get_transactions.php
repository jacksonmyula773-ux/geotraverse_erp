<?php
// backend/api/get_transactions.php
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

// Build query based on user role
if ($user_dept == 1 || $user_role == 'Super Administrator') {
    // Super admin sees all transactions
    $query = "SELECT t.*, d.name as department_name 
              FROM transactions t 
              LEFT JOIN departments d ON t.department_id = d.id 
              ORDER BY t.transaction_date DESC, t.id DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
} else {
    // Other departments see only their transactions
    $query = "SELECT t.*, d.name as department_name 
              FROM transactions t 
              LEFT JOIN departments d ON t.department_id = d.id 
              WHERE t.department_id = ? 
              ORDER BY t.transaction_date DESC, t.id DESC";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_dept]);
}

$transactions = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $transactions[] = $row;
}

// Calculate financial summary
$total_income = 0;
$total_expenses = 0;
$total_paid = 0;
$total_pending = 0;

foreach ($transactions as $t) {
    if ($t['type'] == 'income') {
        $total_income += $t['amount'];
        if ($t['status'] == 'paid') {
            $total_paid += $t['amount'];
        } else {
            $total_pending += $t['amount'];
        }
    } else {
        $total_expenses += $t['amount'];
    }
}

echo json_encode([
    'success' => true,
    'count' => count($transactions),
    'summary' => [
        'total_income' => $total_income,
        'total_expenses' => $total_expenses,
        'net_profit' => $total_income - $total_expenses,
        'total_paid' => $total_paid,
        'total_pending' => $total_pending
    ],
    'data' => $transactions
]);
?>