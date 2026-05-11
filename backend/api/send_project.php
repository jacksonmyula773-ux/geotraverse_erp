<?php
// backend/api/send_project.php
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['project_id']) || !isset($data['to_department_id'])) {
    sendResponse(false, null, "Project ID and destination department required");
}

$database = new Database();
$db = $database->getConnection();

$query = "UPDATE projects SET department_id = :to_dept, sent_from_dept = :from_dept WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':to_dept', $data['to_department_id']);
$stmt->bindParam(':from_dept', $data['from_department_id'] ?? 1);
$stmt->bindParam(':id', $data['project_id']);

if ($stmt->execute()) {
    sendResponse(true, null, "Project sent successfully");
} else {
    sendResponse(false, null, "Failed to send project");
}
?>