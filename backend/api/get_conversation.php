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

$conversation_id = isset($input['conversation_id']) ? intval($input['conversation_id']) : 0;
$user_id = isset($input['user_id']) ? intval($input['user_id']) : 1;

if ($conversation_id === 0) {
    echo json_encode(["success" => false, "message" => "Missing conversation_id"]);
    exit();
}

// Soft delete all messages in this conversation for this user
$update1 = $pdo->prepare("UPDATE messages SET sender_deleted = 1, deleted_at = NOW() 
                         WHERE conversation_id = ? AND sender_id = ?");
$update1->execute([$conversation_id, $user_id]);

$update2 = $pdo->prepare("UPDATE messages SET receiver_deleted = 1, deleted_at = NOW() 
                         WHERE conversation_id = ? AND receiver_id = ?");
$update2->execute([$conversation_id, $user_id]);

echo json_encode(["success" => true, "message" => "Conversation deleted successfully"]);
exit();
?>