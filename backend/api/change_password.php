<?php
// backend/api/change_password.php
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['current_password']) || !isset($data['new_password'])) {
    sendResponse(false, null, "Current and new password required");
}

if (strlen($data['new_password']) < 4) {
    sendResponse(false, null, "New password must be at least 4 characters");
}

$database = new Database();
$db = $database->getConnection();

// Get current user (assuming admin id = 1 for now)
$userId = $_SESSION['user_id'] ?? 1;

$query = "SELECT password FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $userId);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (password_verify($data['current_password'], $user['password'])) {
        $newPassword = password_hash($data['new_password'], PASSWORD_DEFAULT);
        $updateQuery = "UPDATE users SET password = :password WHERE id = :id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':password', $newPassword);
        $updateStmt->bindParam(':id', $userId);
        
        if ($updateStmt->execute()) {
            sendResponse(true, null, "Password changed successfully");
        } else {
            sendResponse(false, null, "Failed to change password");
        }
    } else {
        sendResponse(false, null, "Current password is incorrect");
    }
} else {
    sendResponse(false, null, "User not found");
}
?>