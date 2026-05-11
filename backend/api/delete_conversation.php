<?php
// backend/api/delete_conversation.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

error_log("=== DELETE CONVERSATION API CALLED ===");

$inputJSON = file_get_contents("php://input");
error_log("Raw input: " . $inputJSON);

$data = json_decode($inputJSON, true);

if (!$data) {
    $data = $_POST;
}

error_log("Processed data: " . print_r($data, true));

if (!isset($data['conversation_id']) || empty($data['conversation_id'])) {
    sendResponse(false, null, "Conversation ID is required");
}

$conversationId = intval($data['conversation_id']);

$database = new Database();
$db = $database->getConnection();

// Start transaction
$db->beginTransaction();

try {
    // First, check if conversation exists and belongs to admin
    $checkQuery = "SELECT id FROM conversations WHERE id = :conv_id AND admin_id = 1";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':conv_id', $conversationId);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        $db->rollBack();
        sendResponse(false, null, "Conversation not found or access denied");
    }
    
    // Delete all messages in this conversation
    $msgQuery = "DELETE FROM messages WHERE conversation_id = :conv_id";
    $msgStmt = $db->prepare($msgQuery);
    $msgStmt->bindParam(':conv_id', $conversationId);
    $msgStmt->execute();
    error_log("Deleted " . $msgStmt->rowCount() . " messages");
    
    // Delete the conversation
    $convQuery = "DELETE FROM conversations WHERE id = :conv_id";
    $convStmt = $db->prepare($convQuery);
    $convStmt->bindParam(':conv_id', $conversationId);
    $convStmt->execute();
    error_log("Deleted conversation");
    
    $db->commit();
    sendResponse(true, null, "Conversation deleted successfully");
    
} catch (Exception $e) {
    $db->rollBack();
    error_log("Delete conversation error: " . $e->getMessage());
    sendResponse(false, null, "Failed to delete conversation: " . $e->getMessage());
}
?>