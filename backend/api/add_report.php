<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

require_once '../config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$title = $data['title'] ?? '';
$period = $data['period'] ?? 'monthly';
$content = $data['content'] ?? '';
$department_id = $data['department_id'] ?? ($_SESSION['department_id'] ?? 1);

if (!$title || !$content) {
    echo json_encode(['success' => false, 'message' => 'Title and content required']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO reports (title, period, content, department_id, status, created_at) VALUES (?, ?, ?, ?, 'draft', NOW())");
$stmt->bind_param("sssi", $title, $period, $content, $department_id);
$stmt->execute();

echo json_encode(['success' => true, 'id' => $conn->insert_id]);
?>