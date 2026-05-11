<?php
// backend/api/mark_report_viewed.php
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['report_id'])) {
    sendResponse(false, null, "Report ID required");
}

$database = new Database();
$db = $database->getConnection();

$query = "UPDATE reports SET is_viewed_by_admin = 1 WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $data['report_id']);

if ($stmt->execute()) {
    $countQuery = "SELECT COUNT(*) as unviewed FROM reports WHERE is_viewed_by_admin = 0 AND department_id != 1";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute();
    $count = $countStmt->fetch(PDO::FETCH_ASSOC);
    
    sendResponse(true, null, "Report marked as viewed", $count['unviewed']);
} else {
    sendResponse(false, null, "Failed to mark report as viewed");
}
?>