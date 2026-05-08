<?php
require_once 'db_connection.php';

$data = json_decode(file_get_contents('php://input'), true);

$currentPassword = isset($data['current_password']) ? trim($data['current_password']) : '';
$newPassword = isset($data['new_password']) ? trim($data['new_password']) : '';

if (!$currentPassword || !$newPassword) {
    echo json_encode(['success' => false, 'message' => 'Current password and new password required']);
    exit;
}

if (strlen($newPassword) < 4) {
    echo json_encode(['success' => false, 'message' => 'New password must be at least 4 characters']);
    exit;
}

// Hash passwords with MD5 (same as database)
$hashedCurrent = md5($currentPassword);
$hashedNew = md5($newPassword);

// Update password for Super Admin (department_id = 1)
$query = "UPDATE users SET password = ? WHERE department_id = 1 AND password = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $hashedNew, $hashedCurrent);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
}

$stmt->close();
$conn->close();
?>