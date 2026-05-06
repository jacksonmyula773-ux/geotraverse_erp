<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? $_GET['id'] : null;

if ($id) {
    $query = "SELECT u.*, d.name as department_name 
              FROM users u 
              LEFT JOIN departments d ON u.department_id = d.id 
              WHERE u.id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
} else {
    $query = "SELECT u.*, d.name as department_name 
              FROM users u 
              LEFT JOIN departments d ON u.department_id = d.id 
              ORDER BY u.id DESC";
    $stmt = $db->prepare($query);
}

$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($employees as &$emp) {
    unset($emp['password']);
}

echo json_encode(['success' => true, 'data' => $employees]);
?>