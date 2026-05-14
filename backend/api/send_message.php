<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
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

$sender_id = isset($input['sender_id']) ? intval($input['sender_id']) : (isset($input['user_id']) ? intval($input['user_id']) : 0);
$receiver_department_id = isset($input['receiver_department_id']) ? intval($input['receiver_department_id']) : 0;
$department_id = isset($input['department_id']) ? intval($input['department_id']) : 0;
$conversation_id = isset($input['conversation_id']) ? intval($input['conversation_id']) : 0;
$message = isset($input['message']) ? trim($input['message']) : '';

if ($sender_id === 0) {
    echo json_encode(["success" => false, "message" => "Sender ID required"]);
    exit();
}

if (empty($message)) {
    echo json_encode(["success" => false, "message" => "Message cannot be empty"]);
    exit();
}

$target_department = $receiver_department_id > 0 ? $receiver_department_id : $department_id;

// SPECIAL CASE: If target is Super Admin (value 1)
if ($target_department == 1) {
    // Find Super Admin user (role = 'Super Administrator' or user_id = 1)
    $superAdminQuery = $pdo->prepare("
        SELECT id FROM users 
        WHERE (role = 'Super Administrator' OR id = 1) 
        AND is_active = 1 
        LIMIT 1
    ");
    $superAdminQuery->execute();
    $superAdmin = $superAdminQuery->fetch(PDO::FETCH_ASSOC);
    
    if ($superAdmin) {
        $receiver_id = $superAdmin['id'];
    } else {
        // Fallback: use user_id = 1
        $receiver_id = 1;
    }
}

if ($conversation_id > 0) {
    // Existing conversation
    $getConv = $pdo->prepare("SELECT user_id, admin_id FROM conversations WHERE id = ?");
    $getConv->execute([$conversation_id]);
    $conv = $getConv->fetch(PDO::FETCH_ASSOC);
    
    if (!$conv) {
        echo json_encode(["success" => false, "message" => "Conversation not found"]);
        exit();
    }
    
    $receiver_id = ($conv['user_id'] == $sender_id) ? $conv['admin_id'] : $conv['user_id'];
    
    $updateConv = $pdo->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = ?");
    $updateConv->execute([$conversation_id]);
} 
else if ($target_department > 0 && !isset($receiver_id)) {
    // New conversation - find receiver in target department
    $receiverQuery = $pdo->prepare("
        SELECT id FROM users 
        WHERE department_id = ? AND is_active = 1 
        LIMIT 1
    ");
    $receiverQuery->execute([$target_department]);
    $receiver = $receiverQuery->fetch(PDO::FETCH_ASSOC);
    
    if (!$receiver) {
        echo json_encode(["success" => false, "message" => "No active users found in selected department"]);
        exit();
    }
    
    $receiver_id = $receiver['id'];
} 
else if (!isset($receiver_id)) {
    echo json_encode(["success" => false, "message" => "Please select a department"]);
    exit();
}

// Check if conversation already exists
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
    $insertConv = $pdo->prepare("
        INSERT INTO conversations (user_id, admin_id, subject, created_at, updated_at) 
        VALUES (?, ?, 'New Message', NOW(), NOW())
    ");
    $insertConv->execute([$sender_id, $receiver_id]);
    $conversation_id = $pdo->lastInsertId();
}

// Insert message
$insertMsg = $pdo->prepare("
    INSERT INTO messages (conversation_id, sender_id, receiver_id, message, status, created_at) 
    VALUES (?, ?, ?, ?, 'sent', NOW())
");
$insertMsg->execute([$conversation_id, $sender_id, $receiver_id, $message]);

echo json_encode([
    "success" => true, 
    "message" => "Message sent successfully", 
    "conversation_id" => $conversation_id,
    "receiver_id" => $receiver_id
]);
?>