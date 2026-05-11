<?php
// backend/api/delete_employee.php
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'])) {
    sendResponse(false, null, "Employee ID required");
}

$database = new Database();
$db = $database->getConnection();

// Don't allow deleting admin user (id=1)
if ($data['id'] == 1) {
    sendResponse(false, null, "Cannot delete super admin");
}

$query = "DELETE FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $data['id']);

if ($stmt->execute()) {
    sendResponse(true, null, "Employee deleted successfully");
} else {
    sendResponse(false, null, "Failed to delete employee");
}
?>