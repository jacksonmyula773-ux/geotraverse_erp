<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$host = "localhost";
$db_name = "geotraverse_erp";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=" . $host . ";dbname=" . $db_name . ";charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database connection failed", "data" => []]);
    exit();
}

// Get department_id from request
$department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;

if ($department_id > 0) {
    // For department: show reports where department_id = this department
    // AND NOT deleted by this department (deleted_by_department = 0)
    $query = "SELECT r.*, d.name as department_name 
              FROM reports r
              LEFT JOIN departments d ON r.department_id = d.id
              WHERE r.department_id = ? 
              AND (r.deleted_by_department = 0 OR r.deleted_by_department IS NULL)
              ORDER BY r.id DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$department_id]);
} else {
    // For Admin: show reports where NOT deleted_by_admin
    $query = "SELECT r.*, d.name as department_name 
              FROM reports r
              LEFT JOIN departments d ON r.department_id = d.id
              WHERE (r.deleted_by_admin = 0 OR r.deleted_by_admin IS NULL)
              ORDER BY r.id DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
}

$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(["success" => true, "data" => $reports]);
?>