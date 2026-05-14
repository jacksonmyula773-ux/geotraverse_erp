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
    echo json_encode(["success" => false, "message" => "Database connection failed: " . $e->getMessage()]);
    exit();
}

$input = json_decode(file_get_contents("php://input"), true);
if (!$input) {
    echo json_encode(["success" => false, "message" => "Invalid request data"]);
    exit();
}

$sender_id = isset($input['sender_id']) ? intval($input['sender_id']) : 0;
$receiver_department_id = isset($input['receiver_department_id']) ? intval($input['receiver_department_id']) : 0;
$conversation_id = isset($input['conversation_id']) ? intval($input['conversation_id']) : 0;
$message = isset($input['message']) ? trim($input['message']) : '';
$subject = isset($input['subject']) ? $input['subject'] : 'New Message';

if ($sender_id === 0) {
    echo json_encode(["success" => false, "message" => "Sender ID required"]);
    exit();
}

if (empty($message)) {
    echo json_encode(["success" => false, "message" => "Message cannot be empty"]);
    exit();
}

// Get sender's department
$senderQuery = $pdo->prepare("SELECT department_id FROM users WHERE id = ?");
$senderQuery->execute([$sender_id]);
$sender = $senderQuery->fetch(PDO::FETCH_ASSOC);

if (!$sender) {
    echo json_encode(["success" => false, "message" => "Sender not found"]);
    exit();
}

$sender_dept_id = $sender['department_id'];

// If sending to department (new conversation)
if ($receiver_department_id > 0 && $conversation_id === 0) {
    // Prevent sending to self
    if ($sender_dept_id == $receiver_department_id) {
        echo json_encode(["success" => false, "message" => "You cannot send message to your own department"]);
        exit();
    }
    
    // Find a user in the receiver department (any active user)
    $receiverQuery = $pdo->prepare("SELECT id FROM users WHERE department_id = ? AND is_active = 1 LIMIT 1");
    $receiverQuery->execute([$receiver_department_id]);
    $receiver = $receiverQuery->fetch(PDO::FETCH_ASSOC);
    
    if (!$receiver) {
        echo json_encode(["success" => false, "message" => "No active users in selected department"]);
        exit();
    }
    
    $receiver_id = $receiver['id'];
    
    // Check if conversation exists between these two users
    $convCheck = $pdo->prepare("
        SELECT id FROM conversations 
        WHERE (user_id = ? AND admin_id = ?) OR (user_id = ? AND admin_id = ?)
        LIMIT 1
    ");
    $convCheck->execute([$sender_id, $receiver_id, $receiver_id, $sender_id]);
    $existing = $convCheck->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        $conversation_id = $existing['id'];
        $updateConv = $pdo->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = ?");
        $updateConv->execute([$conversation_id]);
    } else {
        $insertConv = $pdo->prepare("INSERT INTO conversations (user_id, admin_id, subject, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
        $insertConv->execute([$sender_id, $receiver_id, $subject]);
        $conversation_id = $pdo->lastInsertId();
    }
} 
else if ($conversation_id > 0) {
    // Get receiver from existing conversation
    $getConv = $pdo->prepare("SELECT user_id, admin_id FROM conversations WHERE id = ?");
    $getConv->execute([$conversation_id]);
    $conv = $getConv->fetch(PDO::FETCH_ASSOC);
    
    if (!$conv) {
        echo json_encode(["success" => false, "message" => "Conversation not found"]);
        exit();
    }
    
    // Determine receiver (the other person in conversation)
    $receiver_id = ($conv['user_id'] == $sender_id) ? $conv['admin_id'] : $conv['user_id'];
} 
else {
    echo json_encode(["success" => false, "message" => "Either receiver_department_id or conversation_id required"]);
    exit();
}

// Insert message
$insertMsg = $pdo->prepare("
    INSERT INTO messages (conversation_id, sender_id, receiver_id, message, status, created_at) 
    VALUES (?, ?, ?, ?, 'sent', NOW())
");
$insertMsg->execute([$conversation_id, $sender_id, $receiver_id, $message]);

echo json_encode(["success" => true, "message" => "Message sent successfully", "conversation_id" => $conversation_id]);
?>