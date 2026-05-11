<?php
// backend/api/update_project.php
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'])) {
    sendResponse(false, null, "Project ID required");
}

$database = new Database();
$db = $database->getConnection();

$fields = [];
$params = [':id' => $data['id']];

$allowed_fields = ['name', 'client_name', 'amount', 'location', 'description', 'status', 'progress', 'department_id'];
foreach ($allowed_fields as $field) {
    if (isset($data[$field])) {
        $fields[] = "$field = :$field";
        $params[":$field"] = $data[$field];
    }
}

// Handle image update
if (isset($data['image']) && !empty($data['image'])) {
    if (strpos($data['image'], 'data:image') === 0) {
        $upload_dir = dirname(__DIR__, 2) . '/frontend/assets/uploads/projects/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $image_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $data['image']));
        $image_name = 'project_' . time() . '_' . uniqid() . '.png';
        $fields[] = "image = :image";
        $params[':image'] = $image_name;
        file_put_contents($upload_dir . $image_name, $image_data);
    }
}

if (empty($fields)) {
    sendResponse(false, null, "No fields to update");
}

$query = "UPDATE projects SET " . implode(", ", $fields) . " WHERE id = :id";
$stmt = $db->prepare($query);

if ($stmt->execute($params)) {
    sendResponse(true, null, "Project updated successfully");
} else {
    sendResponse(false, null, "Failed to update project");
}
?>