<?php
require_once 'db_connection.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit;
}

$projectId = isset($data['project_id']) ? intval($data['project_id']) : null;
$toDepartmentId = isset($data['to_department_id']) ? intval($data['to_department_id']) : null;
$message = isset($data['message']) ? trim($data['message']) : '';

if (!$projectId || !$toDepartmentId) {
    echo json_encode(['success' => false, 'message' => 'Project ID and department required']);
    exit;
}

// Get project details
$projQuery = "SELECT name, amount, client_name, description FROM projects WHERE id = $projectId";
$projResult = $conn->query($projQuery);
$project = $projResult->fetch_assoc();

if (!$project) {
    echo json_encode(['success' => false, 'message' => 'Project not found']);
    exit;
}

// Get Super Admin user
$adminQuery = "SELECT id FROM users WHERE department_id = 1 LIMIT 1";
$adminResult = $conn->query($adminQuery);
$admin = $adminResult->fetch_assoc();

if (!$admin) {
    echo json_encode(['success' => false, 'message' => 'Super Admin user not found']);
    exit;
}

// Get target department user
$targetQuery = "SELECT id FROM users WHERE department_id = $toDepartmentId LIMIT 1";
$targetResult = $conn->query($targetQuery);
$target = $targetResult->fetch_assoc();

if (!$target) {
    echo json_encode(['success' => false, 'message' => 'Department user not found']);
    exit;
}

// Create message content
$msgContent = "📋 PROJECT SHARED:\n\n";
$msgContent .= "Project: " . $project['name'] . "\n";
$msgContent .= "Client: " . ($project['client_name'] ?? 'N/A') . "\n";
$msgContent .= "Amount: " . number_format($project['amount'], 2) . " TZS\n";
if (!empty($project['description'])) $msgContent .= "Description: " . $project['description'] . "\n\n";
if (!empty($message)) $msgContent .= "📝 Message: " . $message . "\n\n";
$msgContent .= "--- Shared from Super Admin ---";

// Find or create conversation
$convQuery = "SELECT id FROM conversations WHERE (user_id = {$admin['id']} AND admin_id = {$target['id']}) OR (user_id = {$target['id']} AND admin_id = {$admin['id']})";
$convResult = $conn->query($convQuery);
$conversationId = null;

if ($convResult && $convResult->num_rows > 0) {
    $conv = $convResult->fetch_assoc();
    $conversationId = $conv['id'];
} else {
    $insertConv = "INSERT INTO conversations (user_id, admin_id, subject, status, created_at) VALUES ({$admin['id']}, {$target['id']}, 'Project Shared', 'active', NOW())";
    $conn->query($insertConv);
    $conversationId = $conn->insert_id;
}

// Insert message
$escapedMsg = mysqli_real_escape_string($conn, $msgContent);
$insertMsg = "INSERT INTO messages (conversation_id, sender_id, receiver_id, message, status, created_at) 
              VALUES ($conversationId, {$admin['id']}, {$target['id']}, '$escapedMsg', 'sent', NOW())";

if ($conn->query($insertMsg)) {
    echo json_encode(['success' => true, 'message' => 'Project sent successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send project: ' . $conn->error]);
}

$conn->close();
?>