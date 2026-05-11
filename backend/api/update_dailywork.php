<?php
// backend/api/update_dailywork.php
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'])) {
    sendResponse(false, null, "Daily work ID required");
}

$database = new Database();
$db = $database->getConnection();

$fields = [];
$params = [':id' => $data['id']];

$allowed_fields = ['date', 'project_name', 'work_description', 'income', 'expenses', 'paid_amount', 'status', 'department_id'];
foreach ($allowed_fields as $field) {
    if (isset($data[$field])) {
        $fields[] = "$field = :$field";
        $params[":$field"] = $data[$field];
    }
}

if (empty($fields)) {
    sendResponse(false, null, "No fields to update");
}

$query = "UPDATE daily_work SET " . implode(", ", $fields) . " WHERE id = :id";
$stmt = $db->prepare($query);

if ($stmt->execute($params)) {
    sendResponse(true, null, "Daily work updated successfully");
} else {
    sendResponse(false, null, "Failed to update daily work");
}
?>