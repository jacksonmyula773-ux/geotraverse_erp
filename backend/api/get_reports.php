<?php
// backend/api/get_reports.php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get unviewed count for admin
$unviewedQuery = "SELECT COUNT(*) as unviewed FROM reports WHERE is_viewed_by_admin = 0 AND department_id != 1";
$unviewedStmt = $db->prepare($unviewedQuery);
$unviewedStmt->execute();
$unviewed = $unviewedStmt->fetch(PDO::FETCH_ASSOC);

$query = "SELECT r.*, d.name as department_name 
          FROM reports r
          LEFT JOIN departments d ON r.department_id = d.id
          ORDER BY r.id DESC";
$stmt = $db->prepare($query);
$stmt->execute();

$reports = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $reports[] = $row;
}

sendResponse(true, $reports, "", $unviewed['unviewed']);
?>