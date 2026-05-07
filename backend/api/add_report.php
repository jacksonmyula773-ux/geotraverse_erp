<?php
// backend/api/add_report.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'));

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

if (empty($data->title)) {
    echo json_encode(['success' => false, 'message' => 'Title required']);
    exit();
}

if (empty($data->content)) {
    echo json_encode(['success' => false, 'message' => 'Content required']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$title = $data->title;
$period = $data->period ?? 'monthly';
$content = $data->content;
$department_id = $_SESSION['department_id'];
$status = $data->status ?? 'draft';

$query = "INSERT INTO reports (title, period, content, department_id, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
$stmt = $db->prepare($query);

if ($stmt->execute([$title, $period, $content, $department_id, $status])) {
    echo json_encode([
        'success' => true,
        'message' => 'Report added successfully',
        'data' => ['id' => $db->lastInsertId()]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add report']);
}
?>