<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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

// First, check if sent_to_department column exists
$checkColumn = $pdo->query("SHOW COLUMNS FROM reports LIKE 'sent_to_department'");
if ($checkColumn->rowCount() == 0) {
    $pdo->exec("ALTER TABLE reports ADD COLUMN sent_to_department INT DEFAULT NULL");
    $pdo->exec("ALTER TABLE reports ADD COLUMN is_viewed_by_department TINYINT DEFAULT 0");
}

// Update the report - send to department
$update = $pdo->prepare("UPDATE reports SET sent_to_department = ?, status = 'sent', is_viewed_by_department = 0 WHERE id = ?");
$update->execute([$to_department_id, $report_id]);

echo json_encode(["success" => true, "message" => "Report sent successfully to " . $to_department_id]);
?>