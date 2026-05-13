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

$sender_id = isset($input['user_id']) ? intval($input['user_id']) : (isset($input['sender_id']) ? intval($input['sender_id']) : 1);
$department_id = isset($input['department_id']) ? intval($input['department_id']) : 0;
$conversation_id = isset($input['conversation_id']) ? intval($input['conversation_id']) : 0;
$message = isset($input['message']) ? trim($input['message']) : '';

if (empty($message)) {
    echo json_encode(["success" => false, "message" => "Message cannot be empty"]);
    exit();
}

// If sending to department (new conversation)
if ($department_id > 0 && $conversation_id === 0) {
    // Find a user in that department
    $findUser = $pdo->prepare("SELECT id FROM users WHERE department_id = ? AND is_active = 1 LIMIT 1");
    $findUser->execute([$department_id]);
    $receiver = $findUser->fetch(PDO::FETCH_ASSOC);
    
    if (!$receiver) {
        echo json_encode(["success" => false, "message" => "No active users in selected department"]);
        exit();
    }
    
    $receiver_id = $receiver['id'];
    
    // Check if conversation exists
    $checkConv = $pdo->prepare("
        SELECT id FROM conversations 
        WHERE (user_id = ? AND admin_id = ?) OR (user_id = ? AND admin_id = ?)
        LIMIT 1
    ");
    $checkConv->execute([$sender_id, $receiver_id, $receiver_id, $sender_id]);
    $existing = $checkConv->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        $conversation_id = $existing['id'];
        $updateConv = $pdo->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = ?");
        $updateConv->execute([$conversation_id]);
    } else {
        $insertConv = $pdo->prepare("INSERT INTO conversations (user_id, admin_id, subject, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
        $insertConv->execute([$sender_id, $receiver_id, 'New Message']);
        $conversation_id = $pdo->lastInsertId();
    }
} else if ($conversation_id > 0) {
    // Get receiver from existing conversation
    $getConv = $pdo->prepare("SELECT user_id, admin_id FROM conversations WHERE id = ?");
    $getConv->execute([$conversation_id]);
    $conv = $getConv->fetch(PDO::FETCH_ASSOC);
    
    if (!$conv) {
        echo json_encode(["success" => false, "message" => "Conversation not found"]);
        exit();
    }
    
    $receiver_id = ($conv['user_id'] == $sender_id) ? $conv['admin_id'] : $conv['user_id'];
} else {
    echo json_encode(["success" => false, "message" => "Either department_id or conversation_id required"]);
    exit();
}

// Insert message
$insertMsg = $pdo->prepare("
    INSERT INTO messages (conversation_id, sender_id, receiver_id, message, status, created_at) 
    VALUES (?, ?, ?, ?, 'sent', NOW())
");
$insertMsg->execute([$conversation_id, $sender_id, $receiver_id, $message]);

echo json_encode(["success" => true, "message" => "Message sent successfully", "conversation_id" => $conversation_id]);
exit();
?>