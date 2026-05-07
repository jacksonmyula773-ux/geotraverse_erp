<?php
// backend/api/get_employees.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if (!isset($_SESSION['user_id'])) {
    echo '{"success":false,"message":"Not logged in"}';
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo '{"success":false,"message":"Database connection failed"}';
    exit();
}

// Get filter parameters
$role = isset($_GET['role']) ? $_GET['role'] : '';
$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;

$query = "SELECT u.id, u.name, u.email, u.phone, u.role, u.salary, u.join_date, 
                 u.department_id, d.name as department_name,
                 CASE 
                    WHEN u.is_active = 1 THEN 'Active' 
                    ELSE 'Inactive' 
                 END as status
          FROM users u 
          LEFT JOIN departments d ON u.department_id = d.id 
          WHERE 1=1";

$params = [];

if ($role != '') {
    $query .= " AND u.role = ?";
    $params[] = $role;
}

if ($department_id > 0) {
    $query .= " AND u.department_id = ?";
    $params[] = $department_id;
}

$query .= " ORDER BY u.id DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);

$employees = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Super admin can see everything
    if ($_SESSION['department_id'] != 1 && $_SESSION['role'] != 'Super Administrator') {
        unset($row['salary']);
    }
    $employees[] = $row;
}

echo json_encode([
    'success' => true,
    'count' => count($employees),
    'data' => $employees
]);
?>