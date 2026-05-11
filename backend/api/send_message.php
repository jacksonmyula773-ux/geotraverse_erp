<?php
// backend/api/send_message.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

error_log("=== send_message.php called ===");

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    $data = $_POST;
}

error_log("Send message data: " . print_r($data, true));

if (!isset($data['message']) || empty(trim($data['message']))) {
    sendResponse(false, null, "Message content required");
}

$database = new Database();
$db = $database->getConnection();

$message = trim($data['message']);
$sender_id = 1; // Admin ID
$receiver_id = null;
$conversation_id = null;

// If conversation_id provided, use existing conversation
if (isset($data['conversation_id']) && $data['conversation_id'] > 0) {
    $conversation_id = intval($data['conversation_id']);
    
    // Get receiver from conversation
    $convQuery = "SELECT user_id FROM conversations WHERE id = :conv_id AND admin_id = 1";
    $convStmt = $db->prepare($convQuery);
    $convStmt->bindParam(':conv_id', $conversation_id);
    $convStmt->execute();
    $conv = $convStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($conv) {
        $receiver_id = $conv['user_id'];
    } else {
        sendResponse(false, null, "Conversation not found");
    }
} 
// If department_user_id provided, create new conversation or use existing
else if (isset($data['department_user_id'])) {
    $receiver_id = intval($data['department_user_id']);
    
    // Check if conversation exists between admin and this user
    $convQuery = "SELECT id FROM conversations 
                  WHERE (user_id = :user_id AND admin_id = 1)";
    $convStmt = $db->prepare($convQuery);
    $convStmt->bindParam(':user_id', $receiver_id);
    $convStmt->execute();
    $existingConv = $convStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingConv) {
        $conversation_id = $existingConv['id'];
    } else {
        // Create new conversation
        $subject = isset($data['subject']) ? $data['subject'] : 'New Message';
        $insertConv = "INSERT INTO conversations (user_id, admin_id, subject, status, created_at, updated_at) 
                       VALUES (:user_id, 1, :subject, 'active', NOW(), NOW())";
        $convInsert = $db->prepare($insertConv);
        $convInsert->bindParam(':user_id', $receiver_id);
        $convInsert->bindParam(':subject', $subject);
        $convInsert->execute();
        $conversation_id = $db->lastInsertId();
    }
}

if (!$conversation_id || !$receiver_id) {
    sendResponse(false, null, "Could not determine conversation");
}

// Insert message
$query = "INSERT INTO messages (conversation_id, sender_id, receiver_id, message, status, created_at) 
          VALUES (:conv_id, :sender_id, :receiver_id, :message, 'sent', NOW())";
$stmt = $db->prepare($query);
$stmt->bindParam(':conv_id', $conversation_id);
$stmt->bindParam(':sender_id', $sender_id);
$stmt->bindParam(':receiver_id', $receiver_id);
$stmt->bindParam(':message', $message);

if ($stmt->execute()) {
    // Update conversation updated_at timestamp
    $updateConv = "UPDATE conversations SET updated_at = NOW() WHERE id = :conv_id";
    $updateStmt = $db->prepare($updateConv);
    $updateStmt->bindParam(':conv_id', $conversation_id);
    $updateStmt->execute();
    
    sendResponse(true, array(
        'message_id' => $db->lastInsertId(),
        'conversation_id' => $conversation_id
    ), "Message sent successfully");
} else {
    $error = $stmt->errorInfo();
    error_log("Send message error: " . print_r($error, true));
    sendResponse(false, null, "Failed to send message: " . $error[2]);
}
?>