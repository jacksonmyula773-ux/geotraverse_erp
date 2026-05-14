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

$message_id = isset($input['message_id']) ? intval($input['message_id']) : 0;
$user_id = isset($input['user_id']) ? intval($input['user_id']) : 0;

if ($message_id === 0 || $user_id === 0) {
    echo json_encode(["success" => false, "message" => "Missing message_id or user_id"]);
    exit();
}

// Check if user is sender or receiver
$check = $pdo->prepare("SELECT sender_id, receiver_id FROM messages WHERE id = ?");
$check->execute([$message_id]);
$msg = $check->fetch(PDO::FETCH_ASSOC);

if (!$msg) {
    echo json_encode(["success" => false, "message" => "Message not found"]);
    exit();
}

if ($msg['sender_id'] == $user_id) {
    // User is sender - soft delete from sender side only
    $update = $pdo->prepare("UPDATE messages SET sender_deleted = 1, deleted_at = NOW() WHERE id = ?");
    $update->execute([$message_id]);
    echo json_encode(["success" => true, "message" => "Message deleted from your view (sender)"]);
} elseif ($msg['receiver_id'] == $user_id) {
    // User is receiver - soft delete from receiver side only
    $update = $pdo->prepare("UPDATE messages SET receiver_deleted = 1, deleted_at = NOW() WHERE id = ?");
    $update->execute([$message_id]);
    echo json_encode(["success" => true, "message" => "Message deleted from your view (receiver)"]);
} else {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}
?>