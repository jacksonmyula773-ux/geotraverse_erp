<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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

$report_id = isset($input['report_id']) ? intval($input['report_id']) : (isset($input['id']) ? intval($input['id']) : 0);
$user_id = isset($input['user_id']) ? intval($input['user_id']) : 1;
$is_admin = isset($input['is_admin']) ? intval($input['is_admin']) : 1;

if ($report_id === 0) {
    echo json_encode(["success" => false, "message" => "Missing report_id"]);
    exit();
}

if ($is_admin === 1) {
    $update = $pdo->prepare("UPDATE reports SET deleted_by_admin = 1, deleted_at = NOW() WHERE id = ?");
    $update->execute([$report_id]);
} else {
    $update = $pdo->prepare("UPDATE reports SET deleted_by_department = 1, deleted_at = NOW() WHERE id = ?");
    $update->execute([$report_id]);
}

echo json_encode(["success" => true, "message" => "Report deleted successfully"]);
exit();
?>