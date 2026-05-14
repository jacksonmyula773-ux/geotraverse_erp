<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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

$department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// If user_id provided, get department from user
if ($user_id > 0 && $department_id === 0) {
    $userQuery = $pdo->prepare("SELECT department_id FROM users WHERE id = ?");
    $userQuery->execute([$user_id]);
    $user = $userQuery->fetch(PDO::FETCH_ASSOC);
    $department_id = $user ? $user['department_id'] : 0;
}

if ($department_id === 0) {
    echo json_encode(["success" => false, "message" => "Department ID required", "data" => []]);
    exit();
}

// SUPER ADMIN (department_id = 1) - sees reports NOT deleted by admin
if ($department_id == 1) {
    $query = "SELECT r.*, d.name as department_name 
              FROM reports r
              LEFT JOIN departments d ON r.department_id = d.id
              WHERE (r.deleted_by_admin = 0 OR r.deleted_by_admin IS NULL)
              ORDER BY r.id DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} 
// OTHER DEPARTMENTS - see reports NOT deleted by this department
else {
    $query = "
        SELECT r.*, d.name as department_name 
        FROM reports r
        LEFT JOIN departments d ON r.department_id = d.id
        WHERE (r.department_id = ? OR r.sent_to_department = ?)
        AND (r.deleted_by_department = 0 OR r.deleted_by_department IS NULL)
        ORDER BY r.id DESC
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$department_id, $department_id]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode(["success" => true, "data" => $reports]);
?>