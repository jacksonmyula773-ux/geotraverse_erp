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

$title = isset($input['title']) ? trim($input['title']) : '';
$period = isset($input['period']) ? $input['period'] : 'monthly';
$content = isset($input['content']) ? $input['content'] : '';
$department_id = isset($input['department_id']) ? intval($input['department_id']) : 1;

if (empty($title) || empty($content)) {
    echo json_encode(["success" => false, "message" => "Title and content are required"]);
    exit();
}

// Ensure columns exist
$pdo->exec("ALTER TABLE reports ADD COLUMN IF NOT EXISTS is_viewed_by_admin TINYINT DEFAULT 0");
$pdo->exec("ALTER TABLE reports ADD COLUMN IF NOT EXISTS is_viewed_by_department TINYINT DEFAULT 0");

// For Super Admin (department_id = 1), set is_viewed_by_admin = 1 (already viewed)
// For other departments, set is_viewed_by_department = 1
if ($department_id == 1) {
    $insert = $pdo->prepare("INSERT INTO reports (title, period, content, department_id, status, created_at, is_viewed_by_admin) VALUES (?, ?, ?, ?, 'draft', NOW(), 1)");
    $insert->execute([$title, $period, $content, $department_id]);
} else {
    $insert = $pdo->prepare("INSERT INTO reports (title, period, content, department_id, status, created_at, is_viewed_by_department) VALUES (?, ?, ?, ?, 'draft', NOW(), 1)");
    $insert->execute([$title, $period, $content, $department_id]);
}

echo json_encode(["success" => true, "message" => "Report added successfully", "id" => $pdo->lastInsertId()]);
?>