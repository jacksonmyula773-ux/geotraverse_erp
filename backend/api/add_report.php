<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$title = $data['title'] ?? '';
$period = $data['period'] ?? 'monthly';
$content = $data['content'] ?? '';
$department_id = 1; // Super Admin

if (empty($title) || empty($content)) {
    echo json_encode(['success' => false, 'error' => 'Title and content required']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$query = "INSERT INTO reports (department_id, title, period, content, status) 
          VALUES (:dept_id, :title, :period, :content, 'draft')";

$stmt = $db->prepare($query);
$stmt->bindParam(':dept_id', $department_id);
$stmt->bindParam(':title', $title);
$stmt->bindParam(':period', $period);
$stmt->bindParam(':content', $content);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'report_id' => $db->lastInsertId()]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to add report']);
}
?>