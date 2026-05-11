<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['conversation_id'])) {
    sendResponse(false, null, "Conversation ID required");
}

$database = new Database();
$db = $database->getConnection();

// Start transaction
$db->beginTransaction();

try {
    // Delete all messages in conversation
    $msgQuery = "DELETE FROM messages WHERE conversation_id = :conv_id";
    $msgStmt = $db->prepare($msgQuery);
    $msgStmt->bindParam(':conv_id', $data['conversation_id']);
    $msgStmt->execute();
    
    // Delete conversation
    $convQuery = "DELETE FROM conversations WHERE id = :conv_id";
    $convStmt = $db->prepare($convQuery);
    $convStmt->bindParam(':conv_id', $data['conversation_id']);
    $convStmt->execute();
    
    $db->commit();
    sendResponse(true, null, "Conversation deleted");
} catch (Exception $e) {
    $db->rollBack();
    sendResponse(false, null, "Failed to delete conversation: " . $e->getMessage());
}
?>