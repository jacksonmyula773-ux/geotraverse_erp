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
    echo json_encode(["success" => false, "message" => "Database connection failed", "data" => [], "unviewed_count" => 0]);
    exit();
}

$department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($department_id > 0) {
    // For department: Show reports where:
    // 1. department_id = this department (reports created by this department)
    // 2. OR sent_to_department = this department (reports sent to this department)
    // AND NOT deleted by this department
    $query = "SELECT r.*, d.name as department_name 
              FROM reports r
              LEFT JOIN departments d ON r.department_id = d.id
              WHERE (r.department_id = ? OR r.sent_to_department = ?)
              AND (r.deleted_by_department = 0 OR r.deleted_by_department IS NULL)
              ORDER BY r.id DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$department_id, $department_id]);
    
    // Count unviewed reports for this department
    $unviewedQuery = "SELECT COUNT(*) as unviewed FROM reports 
                      WHERE (department_id = ? OR sent_to_department = ?)
                      AND (is_viewed_by_department = 0 OR is_viewed_by_department IS NULL)
                      AND (deleted_by_department = 0 OR deleted_by_department IS NULL)";
    $unviewedStmt = $pdo->prepare($unviewedQuery);
    $unviewedStmt->execute([$department_id, $department_id]);
    $unviewedCount = $unviewedStmt->fetch(PDO::FETCH_ASSOC)['unviewed'];
} else {
    // For Admin: show reports where NOT deleted_by_admin
    $query = "SELECT r.*, d.name as department_name 
              FROM reports r
              LEFT JOIN departments d ON r.department_id = d.id
              WHERE (r.deleted_by_admin = 0 OR r.deleted_by_admin IS NULL)
              ORDER BY r.id DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    
    $unviewedQuery = "SELECT COUNT(*) as unviewed FROM reports 
                      WHERE (is_viewed_by_admin = 0 OR is_viewed_by_admin IS NULL)
                      AND department_id != 1
                      AND (deleted_by_admin = 0 OR deleted_by_admin IS NULL)";
    $unviewedStmt = $pdo->prepare($unviewedQuery);
    $unviewedStmt->execute();
    $unviewedCount = $unviewedStmt->fetch(PDO::FETCH_ASSOC)['unviewed'];
}

$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(["success" => true, "data" => $reports, "unviewed_count" => (int)$unviewedCount]);
?>