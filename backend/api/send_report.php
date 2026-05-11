<?php
// backend/api/send_report.php
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['report_id']) || !isset($data['to_department_id'])) {
    sendResponse(false, null, "Report ID and destination department required");
}

$database = new Database();
$db = $database->getConnection();

$query = "UPDATE reports SET department_id = :to_dept, sent_from_dept = :from_dept, status = 'sent' WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':to_dept', $data['to_department_id']);
$stmt->bindParam(':from_dept', $data['from_department_id'] ?? 1);
$stmt->bindParam(':id', $data['report_id']);

if ($stmt->execute()) {
    sendResponse(true, null, "Report sent successfully");
} else {
    sendResponse(false, null, "Failed to send report");
}
?>