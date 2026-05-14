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

// For Admin: Show projects where deleted_by_admin = 0
// Department will see projects where deleted_by_department = 0
$query = "SELECT p.*, d.name as department_name 
          FROM projects p
          LEFT JOIN departments d ON p.department_id = d.id
          WHERE (p.deleted_by_admin = 0 OR p.deleted_by_admin IS NULL)
          ORDER BY p.id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute();
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count unviewed projects (not deleted by admin)
$unviewedQuery = "SELECT COUNT(*) as unviewed FROM projects 
                  WHERE (is_viewed_by_admin = 0 OR is_viewed_by_admin IS NULL) 
                  AND department_id != 1 
                  AND (deleted_by_admin = 0 OR deleted_by_admin IS NULL)";
$unviewedStmt = $pdo->prepare($unviewedQuery);
$unviewedStmt->execute();
$unviewedCount = $unviewedStmt->fetch(PDO::FETCH_ASSOC)['unviewed'];

echo json_encode(["success" => true, "data" => $projects, "unviewed_count" => (int)$unviewedCount]);
?>