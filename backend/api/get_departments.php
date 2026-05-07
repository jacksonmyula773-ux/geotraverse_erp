<?php
// backend/api/get_departments.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo '{"success":false,"message":"Database connection failed"}';
    exit();
}

$query = "SELECT id, name, email FROM departments ORDER BY name";
$result = $db->query($query);
$departments = [];

while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $departments[] = $row;
}

echo json_encode([
    'success' => true,
    'data' => $departments
]);
?>