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

// Accept both sender_id and user_id
$sender_id = isset($input['sender_id']) ? intval($input['sender_id']) : (isset($input['user_id']) ? intval($input['user_id']) : 0);
$receiver_department_id = isset($input['receiver_department_id']) ? intval($input['receiver_department_id']) : 0;
$department_id = isset($input['department_id']) ? intval($input['department_id']) : 0; // For Admin
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

$target_department = $receiver_department_id > 0 ? $receiver_department_id : $department_id;

// If we have a conversation_id, use it
if ($conversation_id > 0) {
    // Get receiver from existing conversation
    $getConv = $pdo->prepare("SELECT user_id, admin_id FROM conversations WHERE id = ?");
    $getConv->execute([$conversation_id]);
    $conv = $getConv->fetch(PDO::FETCH_ASSOC);
    
    if (!$conv) {
        echo json_encode(["success" => false, "message" => "Conversation not found"]);
        exit();
    }
    
    $receiver_id = ($conv['user_id'] == $sender_id) ? $conv['admin_id'] : $conv['user_id'];
    
    // Update conversation timestamp
    $updateConv = $pdo->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = ?");
    $updateConv->execute([$conversation_id]);
} 
else if ($target_department > 0) {
    // Prevent sending to self
    if ($sender['department_id'] == $target_department) {
        echo json_encode(["success" => false, "message" => "You cannot send message to your own department"]);
        exit();
    }
    
    // Find a user in the receiver department
    $receiverQuery = $pdo->prepare("SELECT id FROM users WHERE department_id = ? AND is_active = 1 LIMIT 1");
    $receiverQuery->execute([$target_department]);
    $receiver = $receiverQuery->fetch(PDO::FETCH_ASSOC);
    
    if (!$receiver) {
        echo json_encode(["success" => false, "message" => "No active users in selected department"]);
        exit();
    }
    
    $receiver_id = $receiver['id'];
    
    // Check if conversation exists
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
else {
    echo json_encode(["success" => false, "message" => "Please select a department to send message"]);
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