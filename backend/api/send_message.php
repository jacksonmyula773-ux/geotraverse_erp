<?php
// backend/api/send_message.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

error_log("=== SEND MESSAGE API CALLED ===");

// Get input data
$inputJSON = file_get_contents("php://input");
error_log("Raw input: " . $inputJSON);

$data = json_decode($inputJSON, true);

if (!$data) {
    $data = $_POST;
}

error_log("Processed data: " . print_r($data, true));

// Check required fields
$message = isset($data['message']) ? trim($data['message']) : '';
$departmentId = isset($data['department_id']) ? intval($data['department_id']) : 0;
$conversationId = isset($data['conversation_id']) ? intval($data['conversation_id']) : 0;

if (empty($message)) {
    sendResponse(false, null, "Message content is required");
}

$database = new Database();
$db = $database->getConnection();

// Admin ID is 1
$adminId = 1;

// Find or create user ID for this department
// Get the user associated with this department
$userQuery = "SELECT id FROM users WHERE department_id = :dept_id AND id != 1 LIMIT 1";
$userStmt = $db->prepare($userQuery);
$userStmt->bindParam(':dept_id', $departmentId);
$userStmt->execute();
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user && $departmentId > 0) {
    // Create a default user for this department if none exists
    $deptName = $departmentNames[$departmentId] ?? "Department $departmentId";
    $insertUser = "INSERT INTO users (name, email, password, department_id, role, is_active) 
                   VALUES (:name, :email, '1234', :dept_id, 'Staff', 1)";
    $userStmt = $db->prepare($insertUser);
    $email = strtolower(str_replace(' ', '_', $deptName)) . '@geotraverse.com';
    $userStmt->bindParam(':name', $deptName);
    $userStmt->bindParam(':email', $email);
    $userStmt->bindParam(':dept_id', $departmentId);
    $userStmt->execute();
    $userId = $db->lastInsertId();
} else if ($user) {
    $userId = $user['id'];
} else {
    sendResponse(false, null, "Invalid department ID");
}

error_log("User ID for department {$departmentId}: " . $userId);

// If conversation_id not provided, find or create conversation
if ($conversationId === 0) {
    // Check if conversation exists between admin and this user
    $convQuery = "SELECT id FROM conversations 
                  WHERE (user_id = :user_id AND admin_id = :admin_id) 
                  ORDER BY id DESC LIMIT 1";
    $convStmt = $db->prepare($convQuery);
    $convStmt->bindParam(':user_id', $userId);
    $convStmt->bindParam(':admin_id', $adminId);
    $convStmt->execute();
    $existingConv = $convStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingConv) {
        $conversationId = $existingConv['id'];
        error_log("Found existing conversation ID: " . $conversationId);
    } else {
        // Create new conversation
        $subject = isset($data['subject']) ? $data['subject'] : 'New Message';
        $insertConv = "INSERT INTO conversations (user_id, admin_id, subject, status, created_at, updated_at) 
                       VALUES (:user_id, :admin_id, :subject, 'active', NOW(), NOW())";
        $convInsert = $db->prepare($insertConv);
        $convInsert->bindParam(':user_id', $userId);
        $convInsert->bindParam(':admin_id', $adminId);
        $convInsert->bindParam(':subject', $subject);
        
        if ($convInsert->execute()) {
            $conversationId = $db->lastInsertId();
            error_log("Created new conversation ID: " . $conversationId);
        } else {
            $error = $convInsert->errorInfo();
            error_log("Failed to create conversation: " . print_r($error, true));
            sendResponse(false, null, "Failed to create conversation");
        }
    }
}

// Determine sender and receiver
$senderId = $adminId;
$receiverId = $userId;

error_log("Sending message - conv_id: {$conversationId}, sender: {$senderId}, receiver: {$receiverId}");

// Insert message
$msgQuery = "INSERT INTO messages (conversation_id, sender_id, receiver_id, message, status, created_at) 
             VALUES (:conv_id, :sender_id, :receiver_id, :message, 'sent', NOW())";
$msgStmt = $db->prepare($msgQuery);
$msgStmt->bindParam(':conv_id', $conversationId);
$msgStmt->bindParam(':sender_id', $senderId);
$msgStmt->bindParam(':receiver_id', $receiverId);
$msgStmt->bindParam(':message', $message);

if ($msgStmt->execute()) {
    // Update conversation updated_at
    $updateConv = "UPDATE conversations SET updated_at = NOW() WHERE id = :conv_id";
    $updateStmt = $db->prepare($updateConv);
    $updateStmt->bindParam(':conv_id', $conversationId);
    $updateStmt->execute();
    
    error_log("Message sent successfully. Message ID: " . $db->lastInsertId());
    
    sendResponse(true, [
        'message_id' => $db->lastInsertId(),
        'conversation_id' => $conversationId
    ], "Message sent successfully");
} else {
    $error = $msgStmt->errorInfo();
    error_log("Failed to send message: " . print_r($error, true));
    sendResponse(false, null, "Failed to send message: " . $error[2]);
}

// Department names for creating default users
$departmentNames = [
    2 => "Finance", 3 => "Sales & Marketing", 4 => "Manager",
    5 => "Secretary", 6 => "Bricks & Timber", 7 => "Aluminium",
    8 => "Town Planning", 9 => "Architectural", 10 => "Survey",
    11 => "Construction", 12 => "Hatimiliki"
];
?>