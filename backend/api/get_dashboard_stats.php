<?php
// backend/api/get_dashboard_stats.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if (!isset($_SESSION['user_id']) || $_SESSION['department_id'] != 1) {
    echo '{"success":false,"message":"Unauthorized access"}';
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo '{"success":false,"message":"Database connection failed"}';
    exit();
}

// Get counts
$totalEmployees = $db->query("SELECT COUNT(*) as count FROM users WHERE is_active = 1")->fetch(PDO::FETCH_ASSOC)['count'];
$totalDepartments = $db->query("SELECT COUNT(*) as count FROM departments")->fetch(PDO::FETCH_ASSOC)['count'];
$totalProjects = $db->query("SELECT COUNT(*) as count FROM projects")->fetch(PDO::FETCH_ASSOC)['count'];

// Get total income and expenses
$income = $db->query("SELECT SUM(amount) as total FROM transactions WHERE type = 'income'")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
$expenses = $db->query("SELECT SUM(amount) as total FROM transactions WHERE type = 'expense'")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Get recent employees
$recentStmt = $db->query("SELECT u.id, u.name, u.email, u.role, d.name as department_name 
                          FROM users u 
                          LEFT JOIN departments d ON u.department_id = d.id 
                          WHERE u.is_active = 1 
                          ORDER BY u.id DESC LIMIT 5");
$recentEmployees = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'data' => [
        'total_employees' => $totalEmployees,
        'total_departments' => $totalDepartments,
        'total_projects' => $totalProjects,
        'total_income' => (float)$income,
        'total_expenses' => (float)$expenses,
        'net_profit' => (float)$income - (float)$expenses,
        'recent_employees' => $recentEmployees
    ]
]);
?>