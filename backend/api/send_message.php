<?php
// backend/api/send_message.php - UPDATED
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$host = "localhost";
$db_name = "geotraverse_erp";
$username = "root";
$password = "";

try {
    $db = new PDO("mysql:host=" . $host . ";dbname=" . $db_name . ";charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit();
}

$inputJSON = file_get_contents("php://input");
$data = json_decode($inputJSON, true);

if (!$data) {
    $data = $_POST;
}

$message = isset($data['message']) ? trim($data['message']) : '';
$conversationId = isset($data['conversation_id']) ? intval($data['conversation_id']) : 0;
$departmentId = isset($data['department_id']) ? intval($data['department_id']) : 0;

if (empty($message)) {
    echo json_encode(["success" => false, "message" => "Message content is required"]);
    exit();
}

$adminId = 1;
$userId = null;

// If conversation_id provided, get receiver from conversation
if ($conversationId > 0) {
    $convQuery = "SELECT user_id FROM conversations WHERE id = :conv_id";
    $convStmt = $db->prepare($convQuery);
    $convStmt->bindParam(':conv_id', $conversationId);
    $convStmt->execute();
    $conv = $convStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($conv) {
        $userId = $conv['user_id'];
    } else {
        echo json_encode(["success" => false, "message" => "Conversation not found"]);
        exit();
    }
}
// If department_id provided, find user for that department
else if ($departmentId > 0) {
    $userQuery = "SELECT id FROM users WHERE department_id = :dept_id AND id != 1 LIMIT 1";
    $userStmt = $db->prepare($userQuery);
    $userStmt->bindParam(':dept_id', $departmentId);
    $userStmt->execute();
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(["success" => false, "message" => "No user found for this department"]);
        exit();
    }
    
    $userId = $user['id'];
    
    // Find or create conversation
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
    } else {
        $subject = isset($data['subject']) ? $data['subject'] : 'New Message';
        $insertConv = "INSERT INTO conversations (user_id, admin_id, subject, status, created_at, updated_at) 
                       VALUES (:user_id, :admin_id, :subject, 'active', NOW(), NOW())";
        $convInsert = $db->prepare($insertConv);
        $convInsert->bindParam(':user_id', $userId);
        $convInsert->bindParam(':admin_id', $adminId);
        $convInsert->bindParam(':subject', $subject);
        $convInsert->execute();
        $conversationId = $db->lastInsertId();
    }
} else {
    echo json_encode(["success" => false, "message" => "Conversation ID or Department ID is required"]);
    exit();
}

// Insert message
$msgQuery = "INSERT INTO messages (conversation_id, sender_id, receiver_id, message, status, created_at) 
             VALUES (:conv_id, :sender_id, :receiver_id, :message, 'sent', NOW())";
$msgStmt = $db->prepare($msgQuery);
$msgStmt->bindParam(':conv_id', $conversationId);
$msgStmt->bindParam(':sender_id', $adminId);
$msgStmt->bindParam(':receiver_id', $userId);
$msgStmt->bindParam(':message', $message);

if ($msgStmt->execute()) {
    $updateConv = "UPDATE conversations SET updated_at = NOW() WHERE id = :conv_id";
    $updateStmt = $db->prepare($updateConv);
    $updateStmt->bindParam(':conv_id', $conversationId);
    $updateStmt->execute();
    
    echo json_encode([
        "success" => true, 
        "data" => [
            "message_id" => $db->lastInsertId(),
            "conversation_id" => $conversationId
        ], 
        "message" => "Message sent successfully"
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to send message"]);
}
?>