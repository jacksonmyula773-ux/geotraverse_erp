<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

require_once '../config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$project_id = isset($data['project_id']) ? intval($data['project_id']) : 0;
$to_department_id = isset($data['to_department_id']) ? intval($data['to_department_id']) : 0;
$message = isset($data['message']) ? trim($data['message']) : '';
$sender_dept = isset($_SESSION['department_id']) ? $_SESSION['department_id'] : 1;

if (!$project_id || !$to_department_id) {
    echo json_encode(['success' => false, 'message' => 'Project ID and Department ID required']);
    exit;
}

// Get project details
$stmt = $conn->prepare("SELECT name, client_name, amount, status, progress, location, description FROM projects WHERE id = ?");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();
$project = $result->fetch_assoc();

if (!$project) {
    echo json_encode(['success' => false, 'message' => 'Project not found']);
    exit;
}

// Create tables if they don't exist
$conn->query("CREATE TABLE IF NOT EXISTS `conversations` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `participant_1` int(11) NOT NULL,
    `participant_2` int(11) NOT NULL,
    `last_message` text DEFAULT NULL,
    `last_message_time` datetime DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_conversation` (`participant_1`, `participant_2`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS `messages` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `conversation_id` int(11) NOT NULL,
    `sender_dept` int(11) NOT NULL,
    `receiver_dept` int(11) NOT NULL,
    `message` text NOT NULL,
    `is_read` tinyint(1) DEFAULT 0,
    `read_at` datetime DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `conversation_id` (`conversation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Find or create conversation
$participant_1 = min($sender_dept, $to_department_id);
$participant_2 = max($sender_dept, $to_department_id);

$checkStmt = $conn->prepare("SELECT id FROM conversations WHERE participant_1 = ? AND participant_2 = ?");
$checkStmt->bind_param("ii", $participant_1, $participant_2);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    $conv = $checkResult->fetch_assoc();
    $conversation_id = $conv['id'];
} else {
    $fullMessage = "PROJECT: " . $project['name'];
    $insertStmt = $conn->prepare("INSERT INTO conversations (participant_1, participant_2, last_message, last_message_time) VALUES (?, ?, ?, NOW())");
    $insertStmt->bind_param("iis", $participant_1, $participant_2, $fullMessage);
    $insertStmt->execute();
    $conversation_id = $conn->insert_id;
}

// Create message content
$fullMessage = "📋 PROJECT SHARED:\n\n";
$fullMessage .= "Project: " . $project['name'] . "\n";
$fullMessage .= "Client: " . $project['client_name'] . "\n";
$fullMessage .= "Amount: " . number_format($project['amount'], 0) . " TZS\n";
$fullMessage .= "Status: " . $project['status'] . "\n";
$fullMessage .= "Progress: " . $project['progress'] . "%\n";
$fullMessage .= "Location: " . $project['location'] . "\n";
$fullMessage .= "Description: " . $project['description'] . "\n";

if ($message) {
    $fullMessage .= "\n📝 Message: " . $message;
}

$fullMessage .= "\n\n--- Shared from Super Admin ---";

// Insert message
$msgStmt = $conn->prepare("INSERT INTO messages (conversation_id, sender_dept, receiver_dept, message, created_at) VALUES (?, ?, ?, ?, NOW())");
$msgStmt->bind_param("iiis", $conversation_id, $sender_dept, $to_department_id, $fullMessage);
$msgStmt->execute();

// Update conversation last message
$updateStmt = $conn->prepare("UPDATE conversations SET last_message = ?, last_message_time = NOW() WHERE id = ?");
$updateStmt->bind_param("si", $fullMessage, $conversation_id);
$updateStmt->execute();

echo json_encode(['success' => true, 'message' => 'Project sent successfully']);
?>