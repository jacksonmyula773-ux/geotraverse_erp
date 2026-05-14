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

// Admin sees projects where:
// 1. department_id = 1 (created by admin)
// 2. OR sent_to_department = 1 (sent to admin)
// AND NOT deleted by admin
$query = "SELECT p.*, d.name as department_name 
          FROM projects p
          LEFT JOIN departments d ON p.department_id = d.id
          WHERE (p.department_id = 1 OR p.sent_to_department = 1)
          AND (p.deleted_by_admin = 0 OR p.deleted_by_admin IS NULL)
          ORDER BY p.id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute();
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count unviewed projects for admin
$unviewedQuery = "SELECT COUNT(*) as unviewed FROM projects 
                  WHERE (is_viewed_by_admin = 0 OR is_viewed_by_admin IS NULL)
                  AND department_id != 1
                  AND (deleted_by_admin = 0 OR deleted_by_admin IS NULL)";
$unviewedStmt = $pdo->prepare($unviewedQuery);
$unviewedStmt->execute();
$unviewedCount = $unviewedStmt->fetch(PDO::FETCH_ASSOC)['unviewed'];

echo json_encode(["success" => true, "data" => $projects, "unviewed_count" => (int)$unviewedCount]);
?>