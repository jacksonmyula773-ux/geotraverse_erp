<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db_connection.php';

$data = json_decode(file_get_contents('php://input'), true);

$projectId = $data['project_id'] ?? null;
$toDepartmentId = $data['to_department_id'] ?? null;
$message = $data['message'] ?? '';

if (!$projectId || !$toDepartmentId) {
    echo json_encode(['success' => false, 'message' => 'Project ID and department required']);
    exit;
}

// Get project details
$projQuery = "SELECT name, amount, client_name FROM projects WHERE id = ?";
$stmt = $conn->prepare($projQuery);
$stmt->bind_param("i", $projectId);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

// Get admin user (Super Admin)
$adminQuery = "SELECT id FROM users WHERE department_id = 1 LIMIT 1";
$adminResult = $conn->query($adminQuery);
$admin = $adminResult->fetch_assoc();

// Get target department user
$userQuery = "SELECT id FROM users WHERE department_id = ? LIMIT 1";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("i", $toDepartmentId);
$stmt->execute();
$toUser = $stmt->get_result()->fetch_assoc();

if (!$admin || !$toUser) {
    echo json_encode(['success' => false, 'message' => 'Users not found']);
    exit;
}

// Create message content
$msgContent = "📋 PROJECT SHARED:\n\n";
$msgContent .= "Project: " . $project['name'] . "\n";
$msgContent .= "Client: " . ($project['client_name'] ?? 'N/A') . "\n";
$msgContent .= "Amount: " . number_format($project['amount'], 2) . " TZS\n";
if ($message) $msgContent .= "\n📝 Message: " . $message . "\n";
$msgContent .= "\n--- Shared from Super Admin ---";

// Check conversation
$convQuery = "SELECT id FROM conversations WHERE (user_id = ? AND admin_id = ?) OR (user_id = ? AND admin_id = ?)";
$stmt = $conn->prepare($convQuery);
$stmt->bind_param("iiii", $admin['id'], $toUser['id'], $toUser['id'], $admin['id']);
$stmt->execute();
$conv = $stmt->get_result()->fetch_assoc();

if (!$conv) {
    $insertConv = "INSERT INTO conversations (user_id, admin_id, subject, status, created_at) VALUES (?, ?, 'Project Shared', 'active', NOW())";
    $stmt = $conn->prepare($insertConv);
    $stmt->bind_param("ii", $admin['id'], $toUser['id']);
    $stmt->execute();
    $conversationId = $conn->insert_id;
} else {
    $conversationId = $conv['id'];
}

// Insert message
$insertMsg = "INSERT INTO messages (conversation_id, sender_id, receiver_id, message, status, created_at) VALUES (?, ?, ?, ?, 'sent', NOW())";
$stmt = $conn->prepare($insertMsg);
$stmt->bind_param("iiis", $conversationId, $admin['id'], $toUser['id'], $msgContent);
$stmt->execute();

echo json_encode(['success' => true, 'message' => 'Project sent successfully']);

$stmt->close();
$conn->close();
?>