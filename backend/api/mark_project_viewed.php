<?php
// backend/api/mark_project_viewed.php
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['project_id'])) {
    sendResponse(false, null, "Project ID required");
}

$database = new Database();
$db = $database->getConnection();

$query = "UPDATE projects SET is_viewed_by_admin = 1 WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $data['project_id']);

if ($stmt->execute()) {
    // Get updated unviewed count
    $countQuery = "SELECT COUNT(*) as unviewed FROM projects WHERE is_viewed_by_admin = 0 AND department_id != 1";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute();
    $count = $countStmt->fetch(PDO::FETCH_ASSOC);
    
    sendResponse(true, null, "Project marked as viewed", $count['unviewed']);
} else {
    sendResponse(false, null, "Failed to mark project as viewed");
}
?>