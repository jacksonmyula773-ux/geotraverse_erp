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
$sender_dept = isset($_SESSION['department_id']) ? $_SESSION['department_id'] : 1;
$receiver_dept = isset($data['to_department_id']) ? intval($data['to_department_id']) : 0;
$message = isset($data['message']) ? trim($data['message']) : '';

if (!$receiver_dept || !$message) {
    echo json_encode(['success' => false, 'message' => 'Department and message are required']);
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
$participant_1 = min($sender_dept, $receiver_dept);
$participant_2 = max($sender_dept, $receiver_dept);

$stmt = $conn->prepare("SELECT id FROM conversations WHERE participant_1 = ? AND participant_2 = ?");
$stmt->bind_param("ii", $participant_1, $participant_2);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $conv = $result->fetch_assoc();
    $conversation_id = $conv['id'];
} else {
    $stmt2 = $conn->prepare("INSERT INTO conversations (participant_1, participant_2, last_message, last_message_time) VALUES (?, ?, ?, NOW())");
    $stmt2->bind_param("iis", $participant_1, $participant_2, $message);
    $stmt2->execute();
    $conversation_id = $conn->insert_id;
}

// Insert message
$stmt3 = $conn->prepare("INSERT INTO messages (conversation_id, sender_dept, receiver_dept, message, created_at) VALUES (?, ?, ?, ?, NOW())");
$stmt3->bind_param("iiis", $conversation_id, $sender_dept, $receiver_dept, $message);
$stmt3->execute();

// Update conversation last message
$stmt4 = $conn->prepare("UPDATE conversations SET last_message = ?, last_message_time = NOW() WHERE id = ?");
$stmt4->bind_param("si", $message, $conversation_id);
$stmt4->execute();

echo json_encode(['success' => true, 'message' => 'Message sent successfully', 'conversation_id' => $conversation_id]);
?>