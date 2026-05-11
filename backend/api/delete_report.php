<?php
// backend/api/delete_report.php
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'])) {
    sendResponse(false, null, "Report ID required");
}

$database = new Database();
$db = $database->getConnection();

$query = "DELETE FROM reports WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $data['id']);

if ($stmt->execute()) {
    sendResponse(true, null, "Report deleted successfully");
} else {
    sendResponse(false, null, "Failed to delete report");
}
?>