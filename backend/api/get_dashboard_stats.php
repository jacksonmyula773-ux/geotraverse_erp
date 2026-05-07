<?php
// backend/api/get_dashboard_stats.php
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

// Get total employees
$empQuery = "SELECT COUNT(*) as count FROM users WHERE is_active = 1";
$empStmt = $db->prepare($empQuery);
$empStmt->execute();
$totalEmployees = $empStmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get total projects
$projQuery = "SELECT COUNT(*) as count FROM projects";
$projStmt = $db->prepare($projQuery);
$projStmt->execute();
$totalProjects = $projStmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get unviewed projects
$unviewedProjQuery = "SELECT COUNT(*) as count FROM projects WHERE is_viewed_by_admin = 0 AND department_id != 1";
$unviewedProjStmt = $db->prepare($unviewedProjQuery);
$unviewedProjStmt->execute();
$unviewedProjects = $unviewedProjStmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get total income and expenses
$incomeQuery = "SELECT SUM(amount) as total FROM transactions WHERE type = 'income'";
$incomeStmt = $db->prepare($incomeQuery);
$incomeStmt->execute();
$totalIncome = $incomeStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$expenseQuery = "SELECT SUM(amount) as total FROM transactions WHERE type = 'expense'";
$expenseStmt = $db->prepare($expenseQuery);
$expenseStmt->execute();
$totalExpense = $expenseStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Get unread messages
$msgQuery = "SELECT COUNT(*) as count FROM messages WHERE to_department_id = 1 AND is_read = 0";
$msgStmt = $db->prepare($msgQuery);
$msgStmt->execute();
$unreadMessages = $msgStmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get recent employees
$recentQuery = "SELECT u.id, u.name, u.email, u.role, d.name as department_name 
                FROM users u 
                LEFT JOIN departments d ON u.department_id = d.id 
                WHERE u.is_active = 1 
                ORDER BY u.id DESC LIMIT 5";
$recentStmt = $db->prepare($recentQuery);
$recentStmt->execute();
$recentEmployees = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'data' => [
        'total_employees' => $totalEmployees,
        'total_projects' => $totalProjects,
        'unviewed_projects' => $unviewedProjects,
        'total_income' => (float)$totalIncome,
        'total_expenses' => (float)$totalExpense,
        'net_profit' => (float)$totalIncome - (float)$totalExpense,
        'unread_messages' => $unreadMessages,
        'recent_employees' => $recentEmployees
    ]
]);
?>