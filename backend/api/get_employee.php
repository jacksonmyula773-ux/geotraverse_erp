<?php
// backend/api/get_employee.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo '{"success":false,"message":"Valid employee ID required"}';
    exit();
}

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo '{"success":false,"message":"Database connection failed"}';
    exit();
}

$query = "SELECT u.id, u.name, u.email, u.phone, u.role, u.salary, u.join_date, 
                 u.department_id, d.name as department_name
          FROM users u 
          LEFT JOIN departments d ON u.department_id = d.id 
          WHERE u.id = ? AND u.is_active = 1";

$stmt = $db->prepare($query);
$stmt->execute([$id]);

if ($stmt->rowCount() > 0) {
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $employee]);
} else {
    echo '{"success":false,"message":"Employee not found"}';
}
?>