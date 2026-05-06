<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$with_dept = isset($_GET['with_department_id']) ? $_GET['with_department_id'] : null;

if (!$with_dept) {
    echo json_encode(['success' => false, 'error' => 'Department ID required']);
    exit();
}

$my_dept = 1; // Super Admin

$query = "DELETE FROM messages WHERE (from_department_id = :my_dept AND to_department_id = :with_dept) 
          OR (from_department_id = :with_dept AND to_department_id = :my_dept)";
$stmt = $db->prepare($query);
$stmt->bindParam(':my_dept', $my_dept);
$stmt->bindParam(':with_dept', $with_dept);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to delete conversation']);
}
?>