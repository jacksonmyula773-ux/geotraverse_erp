<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$auth = new Auth();
$user = $auth->validateToken();

if (!$user || $user['role'] !== 'Super Admin') {
    sendResponse(false, null, "Unauthorized", 401);
}

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->id) || !isset($data->new_password)) {
    sendResponse(false, null, "Employee ID and new password required");
}

if (strlen($data->new_password) < 4) {
    sendResponse(false, null, "Password must be at least 4 characters");
}

$newHash = password_hash($data->new_password, PASSWORD_DEFAULT);

$query = "UPDATE users SET password = :password WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':password', $newHash);
$stmt->bindParam(':id', $data->id);

if ($stmt->execute()) {
    sendResponse(true, null, "Password reset successfully");
} else {
    sendResponse(false, null, "Failed to reset password");
}
?>