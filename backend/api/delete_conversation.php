<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once "../config/database.php";

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);

$conversation_id = isset($data['conversation_id']) ? intval($data['conversation_id']) : 0;
$user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;

if ($conversation_id === 0) {
    echo json_encode(["success" => false, "message" => "Missing conversation_id"]);
    exit;
}

if ($user_id === 0) {
    session_start();
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    } else {
        echo json_encode(["success" => false, "message" => "Missing user_id"]);
        exit;
    }
}

// Soft delete all messages for this user
$stmt1 = $conn->prepare("UPDATE messages SET sender_deleted = 1, deleted_at = NOW() 
                         WHERE conversation_id = ? AND sender_id = ?");
$stmt1->bind_param("ii", $conversation_id, $user_id);
$stmt1->execute();

$stmt2 = $conn->prepare("UPDATE messages SET receiver_deleted = 1, deleted_at = NOW() 
                         WHERE conversation_id = ? AND receiver_id = ?");
$stmt2->bind_param("ii", $conversation_id, $user_id);
$stmt2->execute();

echo json_encode(["success" => true, "message" => "Conversation deleted successfully"]);

$conn->close();
?>