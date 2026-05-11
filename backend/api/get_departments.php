<?php
// backend/api/get_departments.php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$query = "SELECT id, name, email, phone, description FROM departments WHERE id != 1 ORDER BY id";
$stmt = $db->prepare($query);
$stmt->execute();

$departments = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $departments[] = $row;
}

sendResponse(true, $departments);
?>