<?php
require_once 'db_connection.php';

$data = json_decode(file_get_contents('php://input'), true);
$departmentId = isset($data['department_id']) ? intval($data['department_id']) : null;

if (!$departmentId) {
    echo json_encode(['success' => false, 'message' => 'Department ID required']);
    exit;
}

// Get Super Admin user
$adminQuery = "SELECT id FROM users WHERE department_id = 1 LIMIT 1";
$adminResult = $conn->query($adminQuery);
$admin = $adminResult->fetch_assoc();

if (!$admin) {
    echo json_encode(['success' => false, 'message' => 'Admin user not found']);
    exit;
}

// Get target department user
$targetQuery = "SELECT id FROM users WHERE department_id = $departmentId LIMIT 1";
$targetResult = $conn->query($targetQuery);
$target = $targetResult->fetch_assoc();

if (!$target) {
    echo json_encode(['success' => false, 'message' => 'Department user not found']);
    exit;
}

// Find conversation
$convQuery = "SELECT id FROM conversations WHERE (user_id = {$admin['id']} AND admin_id = {$target['id']}) OR (user_id = {$target['id']} AND admin_id = {$admin['id']})";
$convResult = $conn->query($convQuery);
$deleted = false;

if ($convResult && $convResult->num_rows > 0) {
    $conv = $convResult->fetch_assoc();
    
    // Delete messages first (avoid foreign key constraint)
    $conn->query("DELETE FROM messages WHERE conversation_id = {$conv['id']}");
    
    // Delete conversation
    if ($conn->query("DELETE FROM conversations WHERE id = {$conv['id']}")) {
        $deleted = true;
    }
}

echo json_encode(['success' => true, 'deleted' => $deleted]);

$conn->close();
?>