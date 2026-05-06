<?php
/**
 * Mark Message as Read
 * Method: PUT / POST
 * Parameters: 
 *   - id (required) - Message ID
 *   - mark_all (optional) - If true, mark all unread messages as read
 */

require_once '../config/database.php';
require_once '../includes/auth.php';

$auth = new Auth();
$user = $auth->validateToken();

if (!$user) {
    sendResponse(false, null, "Unauthorized", 401);
}

$database = new Database();
$db = $database->getConnection();

// Get input
$data = json_decode(file_get_contents("php://input"));
$id = isset($_GET['id']) ? $_GET['id'] : (isset($data->id) ? $data->id : null);
$markAll = isset($_GET['mark_all']) ? $_GET['mark_all'] : (isset($data->mark_all) ? $data->mark_all : false);

if ($markAll === true || $markAll === 'true' || $markAll === 1) {
    // Mark all unread messages for this department as read
    $query = "UPDATE messages 
              SET is_read = 1, read_at = NOW() 
              WHERE to_department_id = :dept_id AND is_read = 0";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':dept_id', $user['department_id']);
    
    if ($stmt->execute()) {
        $updatedCount = $stmt->rowCount();
        logActivity($user['id'], "Marked all messages as read", "Updated $updatedCount messages");
        sendResponse(true, ['updated_count' => $updatedCount], "All messages marked as read");
    } else {
        sendResponse(false, null, "Failed to mark messages as read");
    }
} elseif ($id) {
    // Mark single message as read
    // First verify this message belongs to this department
    $checkQuery = "SELECT id FROM messages 
                   WHERE id = :id AND to_department_id = :dept_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $id);
    $checkStmt->bindParam(':dept_id', $user['department_id']);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() == 0) {
        sendResponse(false, null, "Message not found or you don't have permission", 403);
    }
    
    $query = "UPDATE messages SET is_read = 1, read_at = NOW() WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    
    if ($stmt->execute()) {
        logActivity($user['id'], "Marked message as read", "Message ID: $id");
        sendResponse(true, null, "Message marked as read");
    } else {
        sendResponse(false, null, "Failed to mark message as read");
    }
} else {
    sendResponse(false, null, "Message ID required or set mark_all=true");
}
?>