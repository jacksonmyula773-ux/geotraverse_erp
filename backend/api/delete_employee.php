<?php
// backend/api/delete_employee.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, POST');

require_once '../config/database.php';

// Get POST data
$rawData = file_get_contents('php://input');
$data = json_decode($rawData);

if (!$data) {
    echo '{"success":false,"message":"Invalid JSON data"}';
    exit();
}

if (empty($data->id)) {
    echo '{"success":false,"message":"Employee ID is required"}';
    exit();
}

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo '{"success":false,"message":"Database connection failed"}';
    exit();
}

$id = (int)$data->id;

// Get employee name first
$nameStmt = $db->prepare("SELECT name FROM users WHERE id = ?");
$nameStmt->execute([$id]);
$employee = $nameStmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    echo '{"success":false,"message":"Employee not found"}';
    exit();
}

// Soft delete
$stmt = $db->prepare("UPDATE users SET is_active = 0 WHERE id = ?");

if ($stmt->execute([$id])) {
    echo json_encode([
        'success' => true,
        'message' => 'Employee "' . $employee['name'] . '" deleted successfully'
    ]);
} else {
    echo '{"success":false,"message":"Failed to delete employee"}';
}
?>