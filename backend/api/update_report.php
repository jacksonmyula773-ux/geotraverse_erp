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

$report_id = isset($input['id']) ? intval($input['id']) : (isset($input['report_id']) ? intval($input['report_id']) : 0);
$title = isset($input['title']) ? $input['title'] : '';
$period = isset($input['period']) ? $input['period'] : '';
$content = isset($input['content']) ? $input['content'] : '';
$department_id = isset($input['department_id']) ? intval($input['department_id']) : 1;

if ($report_id === 0) {
    echo json_encode(["success" => false, "message" => "Missing report_id"]);
    exit();
}

// Only update content, not the sent_to_department or viewed status
$update = $pdo->prepare("UPDATE reports SET title = ?, period = ?, content = ? WHERE id = ?");
$update->execute([$title, $period, $content, $report_id]);

echo json_encode(["success" => true, "message" => "Report updated successfully"]);
?>