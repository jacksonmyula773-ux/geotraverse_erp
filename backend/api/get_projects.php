<?php
// backend/api/get_projects.php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get unviewed count for admin (department_id = 1)
$unviewedQuery = "SELECT COUNT(*) as unviewed FROM projects WHERE is_viewed_by_admin = 0 AND department_id != 1";
$unviewedStmt = $db->prepare($unviewedQuery);
$unviewedStmt->execute();
$unviewed = $unviewedStmt->fetch(PDO::FETCH_ASSOC);

$query = "SELECT p.*, d.name as department_name 
          FROM projects p
          LEFT JOIN departments d ON p.department_id = d.id
          ORDER BY p.id DESC";
$stmt = $db->prepare($query);
$stmt->execute();

$projects = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $projects[] = $row;
}

sendResponse(true, $projects, "", $unviewed['unviewed']);
?>