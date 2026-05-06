<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM departments ORDER BY id ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get employee count for each department
foreach ($departments as &$dept) {
    $countQuery = "SELECT COUNT(*) as total FROM users WHERE department_id = :dept_id";
    $countStmt = $db->prepare($countQuery);
    $countStmt->bindParam(':dept_id', $dept['id']);
    $countStmt->execute();
    $count = $countStmt->fetch(PDO::FETCH_ASSOC);
    $dept['employee_count'] = $count['total'];
}

echo json_encode(['success' => true, 'data' => $departments]);
?>