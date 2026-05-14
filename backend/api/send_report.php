<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

$report_id = isset($input['report_id']) ? intval($input['report_id']) : 0;
$to_department_id = isset($input['to_department_id']) ? intval($input['to_department_id']) : 0;

if ($report_id === 0 || $to_department_id === 0) {
    echo json_encode(["success" => false, "message" => "Missing report_id or to_department_id"]);
    exit();
}

// Ensure columns exist
$pdo->exec("ALTER TABLE reports ADD COLUMN IF NOT EXISTS sent_to_department INT DEFAULT NULL");
$pdo->exec("ALTER TABLE reports ADD COLUMN IF NOT EXISTS is_viewed_by_department TINYINT DEFAULT 0");

// Check if report exists
$checkQuery = $pdo->prepare("SELECT id, title, department_id, sent_to_department FROM reports WHERE id = ?");
$checkQuery->execute([$report_id]);
$report = $checkQuery->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    echo json_encode(["success" => false, "message" => "Report not found"]);
    exit();
}

// Update report with sent_to_department
$update = $pdo->prepare("UPDATE reports SET sent_to_department = ?, status = 'sent', is_viewed_by_department = 0 WHERE id = ?");
$update->execute([$to_department_id, $report_id]);

// Verify update worked
$verifyQuery = $pdo->prepare("SELECT sent_to_department FROM reports WHERE id = ?");
$verifyQuery->execute([$report_id]);
$updated = $verifyQuery->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true, 
    "message" => "Report sent successfully",
    "report_id" => $report_id,
    "to_department_id" => $to_department_id,
    "sent_to_department" => $updated ? $updated['sent_to_department'] : null
]);
?>