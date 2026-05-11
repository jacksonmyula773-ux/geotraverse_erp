<?php
// backend/api/delete_dailywork.php
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'])) {
    sendResponse(false, null, "Daily work ID required");
}

$database = new Database();
$db = $database->getConnection();

$query = "DELETE FROM daily_work WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $data['id']);

if ($stmt->execute()) {
    sendResponse(true, null, "Daily work deleted successfully");
} else {
    sendResponse(false, null, "Failed to delete daily work");
}
?>