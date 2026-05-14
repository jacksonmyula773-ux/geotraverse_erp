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
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
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

// Update the report with sent_to_department
$update = $pdo->prepare("UPDATE reports SET sent_to_department = ?, status = 'sent' WHERE id = ?");
$update->execute([$to_department_id, $report_id]);

echo json_encode(["success" => true, "message" => "Report sent successfully"]);
?>