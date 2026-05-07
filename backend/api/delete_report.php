<?php
// backend/api/delete_report.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, POST');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'));

if (!$data || empty($data->id)) {
    echo json_encode(['success' => false, 'message' => 'Report ID required']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$id = (int)$data->id;

$stmt = $db->prepare("DELETE FROM reports WHERE id = ?");

if ($stmt->execute([$id])) {
    echo json_encode(['success' => true, 'message' => 'Report deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete report']);
}
?>