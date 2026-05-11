<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['project_id']) || !isset($data['to_department_id'])) {
    sendResponse(false, null, "Project ID and destination department required");
}

$database = new Database();
$db = $database->getConnection();

$query = "UPDATE projects SET department_id = :to_dept, sent_from_dept = 1 WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':to_dept', $data['to_department_id']);
$stmt->bindParam(':id', $data['project_id']);

if ($stmt->execute()) {
    sendResponse(true, null, "Project sent successfully");
} else {
    sendResponse(false, null, "Failed to send project");
}
?>