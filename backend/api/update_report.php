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

$id = $data['id'] ?? 0;
$title = $data['title'] ?? '';
$period = $data['period'] ?? 'monthly';
$content = $data['content'] ?? '';

if (!$id || !$title) {
    echo json_encode(['success' => false, 'message' => 'ID and title required']);
    exit;
}

$stmt = $conn->prepare("UPDATE reports SET title = ?, period = ?, content = ? WHERE id = ?");
$stmt->bind_param("sssi", $title, $period, $content, $id);
$stmt->execute();

echo json_encode(['success' => true]);
?>