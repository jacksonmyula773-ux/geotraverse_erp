<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
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
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit();
}

$input = json_decode(file_get_contents("php://input"), true);
if (!$input) {
    echo json_encode(["success" => false, "message" => "Invalid request data"]);
    exit();
}

$report_id = isset($input['report_id']) ? intval($input['report_id']) : 0;
$user_id = isset($input['user_id']) ? intval($input['user_id']) : 0;
$is_admin = isset($input['is_admin']) ? intval($input['is_admin']) : 0;

if ($report_id === 0) {
    echo json_encode(["success" => false, "message" => "Missing report_id"]);
    exit();
}

// Get user's department if user_id provided
$department_id = 0;
if ($user_id > 0) {
    $userQuery = $pdo->prepare("SELECT department_id FROM users WHERE id = ?");
    $userQuery->execute([$user_id]);
    $user = $userQuery->fetch(PDO::FETCH_ASSOC);
    $department_id = $user ? $user['department_id'] : 0;
}

$is_admin_user = ($department_id == 1);

if ($is_admin_user || $is_admin == 1) {
    // Admin marking as viewed
    $update = $pdo->prepare("UPDATE reports SET is_viewed_by_admin = 1 WHERE id = ?");
    $update->execute([$report_id]);
    echo json_encode(["success" => true, "message" => "Report marked as viewed by admin"]);
} else {
    // Department marking as viewed
    $update = $pdo->prepare("UPDATE reports SET is_viewed_by_department = 1 WHERE id = ?");
    $update->execute([$report_id]);
    echo json_encode(["success" => true, "message" => "Report marked as viewed"]);
}
?>