<?php
// backend/api/delete_project.php
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'])) {
    sendResponse(false, null, "Project ID required");
}

$database = new Database();
$db = $database->getConnection();

$query = "DELETE FROM projects WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $data['id']);

if ($stmt->execute()) {
    sendResponse(true, null, "Project deleted successfully");
} else {
    sendResponse(false, null, "Failed to delete project");
}
?>