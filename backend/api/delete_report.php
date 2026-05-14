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
    echo json_encode(["success" => false, "message" => "Database connection failed: " . $e->getMessage()]);
    exit();
}

$input = json_decode(file_get_contents("php://input"), true);
if (!$input) {
    echo json_encode(["success" => false, "message" => "Invalid request data"]);
    exit();
}

$report_id = isset($input['report_id']) ? intval($input['report_id']) : (isset($input['id']) ? intval($input['id']) : 0);
$user_id = isset($input['user_id']) ? intval($input['user_id']) : 0;
$department_id = isset($input['department_id']) ? intval($input['department_id']) : 0;

if ($report_id === 0) {
    echo json_encode(["success" => false, "message" => "Missing report_id"]);
    exit();
}

// Get user's department if user_id provided but department_id not
if ($user_id > 0 && $department_id === 0) {
    $userQuery = $pdo->prepare("SELECT department_id FROM users WHERE id = ?");
    $userQuery->execute([$user_id]);
    $user = $userQuery->fetch(PDO::FETCH_ASSOC);
    $department_id = $user ? $user['department_id'] : 0;
}

// Ensure columns exist
$pdo->exec("ALTER TABLE reports ADD COLUMN IF NOT EXISTS deleted_by_admin TINYINT DEFAULT 0");
$pdo->exec("ALTER TABLE reports ADD COLUMN IF NOT EXISTS deleted_by_department TINYINT DEFAULT 0");
$pdo->exec("ALTER TABLE reports ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL");

$is_admin = ($department_id == 1);

// First, get the report to know its current state
$getReport = $pdo->prepare("SELECT department_id, sent_to_department FROM reports WHERE id = ?");
$getReport->execute([$report_id]);
$report = $getReport->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    echo json_encode(["success" => false, "message" => "Report not found"]);
    exit();
}

if ($is_admin) {
    // Admin delete - set deleted_by_admin = 1
    $update = $pdo->prepare("UPDATE reports SET deleted_by_admin = 1, deleted_at = NOW() WHERE id = ?");
    $update->execute([$report_id]);
    echo json_encode(["success" => true, "message" => "Report deleted from admin view"]);
} else {
    // Department delete - set deleted_by_department = 1
    $update = $pdo->prepare("UPDATE reports SET deleted_by_department = 1, deleted_at = NOW() WHERE id = ?");
    $update->execute([$report_id]);
    echo json_encode(["success" => true, "message" => "Report deleted from your view"]);
}
?>