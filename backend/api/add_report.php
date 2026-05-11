<?php
// backend/api/add_report.php
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['title']) || empty($data['title']) || !isset($data['content']) || empty($data['content'])) {
    sendResponse(false, null, "Title and content required");
}

$database = new Database();
$db = $database->getConnection();

$title = $data['title'];
$period = $data['period'] ?? 'monthly';
$content = $data['content'];
$status = $data['status'] ?? 'draft';
$department_id = $data['department_id'] ?? 1;

$query = "INSERT INTO reports (title, period, content, status, department_id) 
          VALUES (:title, :period, :content, :status, :department_id)";
$stmt = $db->prepare($query);
$stmt->bindParam(':title', $title);
$stmt->bindParam(':period', $period);
$stmt->bindParam(':content', $content);
$stmt->bindParam(':status', $status);
$stmt->bindParam(':department_id', $department_id);

if ($stmt->execute()) {
    sendResponse(true, ['id' => $db->lastInsertId()], "Report added successfully");
} else {
    sendResponse(false, null, "Failed to add report");
}
?>