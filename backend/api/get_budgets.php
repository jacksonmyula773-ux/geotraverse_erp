<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;
$current_dept = isset($_SESSION['department_id']) ? $_SESSION['department_id'] : 2;

// If not logged in, use default
if ($department_id == 0 && $current_dept) {
    $department_id = $current_dept;
}

// Create table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS `budget_allocations` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `category` varchar(100) NOT NULL,
    `allocated_amount` decimal(15,2) DEFAULT NULL,
    `used_amount` decimal(15,2) DEFAULT 0.00,
    `year` int(11) DEFAULT NULL,
    `month` int(11) DEFAULT NULL,
    `department_id` int(11) DEFAULT NULL,
    `description` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `department_id` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if ($department_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM budget_allocations WHERE department_id = ? ORDER BY year DESC, month DESC");
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $budgets = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $result = $conn->query("SELECT * FROM budget_allocations ORDER BY year DESC, month DESC");
    $budgets = $result->fetch_all(MYSQLI_ASSOC);
}

echo json_encode(['success' => true, 'data' => $budgets]);
?>