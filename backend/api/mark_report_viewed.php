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
$is_admin = isset($input['is_admin']) ? intval($input['is_admin']) : 0;

if ($report_id === 0) {
    echo json_encode(["success" => false, "message" => "Missing report_id"]);
    exit();
}

if ($is_admin === 1) {
    $update = $pdo->prepare("UPDATE reports SET is_viewed_by_admin = 1 WHERE id = ?");
    $update->execute([$report_id]);
} else {
    $update = $pdo->prepare("UPDATE reports SET is_viewed_by_department = 1 WHERE id = ?");
    $update->execute([$report_id]);
}

echo json_encode(["success" => true, "message" => "Report marked as viewed"]);
?>