<?php
// backend/api/get_pending_payments.php
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

if ($user_dept == 1 || $user_role == 'Super Administrator') {
    $query = "SELECT p.*, d.name as department_name 
              FROM pending_payments p 
              LEFT JOIN departments d ON p.department_id = d.id 
              WHERE p.status != 'paid'
              ORDER BY p.due_date ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
} else {
    $query = "SELECT p.*, d.name as department_name 
              FROM pending_payments p 
              LEFT JOIN departments d ON p.department_id = d.id 
              WHERE p.department_id = ? AND p.status != 'paid'
              ORDER BY p.due_date ASC";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_dept]);
}

$payments = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Check if overdue
    if ($row['due_date'] < date('Y-m-d') && $row['status'] != 'paid') {
        $row['status'] = 'overdue';
    }
    $row['remaining'] = $row['amount'] - $row['paid_amount'];
    $row['percent_paid'] = ($row['paid_amount'] / $row['amount']) * 100;
    $payments[] = $row;
}

$total_remaining = array_sum(array_column($payments, 'remaining'));

echo json_encode([
    'success' => true,
    'count' => count($payments),
    'total_remaining' => $total_remaining,
    'data' => $payments
]);
?>