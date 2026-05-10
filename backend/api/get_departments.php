<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

require_once '../config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$stmt = $conn->prepare("SELECT id, name, email, phone, description FROM departments ORDER BY id");
$stmt->execute();
$result = $stmt->get_result();
$departments = [];

while ($row = $result->fetch_assoc()) {
    $departments[] = $row;
}

echo json_encode(['success' => true, 'data' => $departments]);
?>