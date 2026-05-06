<?php
/**
 * Get Uploaded Reports
 * Method: GET
 */

require_once '../config/database.php';
require_once '../includes/auth.php';

$auth = new Auth();
$user = $auth->validateToken();

if (!$user) {
    sendResponse(false, null, "Unauthorized", 401);
}

$database = new Database();
$db = $database->getConnection();

$query = "SELECT ur.*, d.name as department_name, u.name as uploaded_by_name 
          FROM uploaded_reports ur
          LEFT JOIN departments d ON ur.department_id = d.id
          LEFT JOIN users u ON ur.uploaded_by = u.id
          ORDER BY ur.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute();
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

sendResponse(true, $reports);
?>