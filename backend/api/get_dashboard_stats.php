<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$empStmt = $db->query("SELECT COUNT(*) as total FROM users");
$employees = $empStmt->fetch(PDO::FETCH_ASSOC);

$projStmt = $db->query("SELECT COUNT(*) as total FROM projects");
$projects = $projStmt->fetch(PDO::FETCH_ASSOC);

$transStmt = $db->query("SELECT 
    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expenses
    FROM transactions");
$finance = $transStmt->fetch(PDO::FETCH_ASSOC);

$msgStmt = $db->query("SELECT COUNT(*) as total FROM messages WHERE to_department_id = 1 AND is_read = 0");
$messages = $msgStmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'data' => [
        'employees' => $employees['total'],
        'projects' => $projects['total'],
        'total_income' => $finance['total_income'] ?? 0,
        'total_expenses' => $finance['total_expenses'] ?? 0,
        'unread_messages' => $messages['total'] ?? 0
    ]
]);
?>