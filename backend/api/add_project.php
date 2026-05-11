<?php
// backend/api/add_project.php
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['name']) || empty($data['name'])) {
    sendResponse(false, null, "Project name required");
}

$database = new Database();
$db = $database->getConnection();

$name = $data['name'];
$client_name = $data['client_name'] ?? null;
$amount = $data['amount'] ?? 0;
$location = $data['location'] ?? null;
$description = $data['description'] ?? null;
$status = $data['status'] ?? 'pending';
$progress = $data['progress'] ?? 0;
$department_id = $data['department_id'] ?? 1;
$image = $data['image'] ?? null;
$start_date = $data['start_date'] ?? date('Y-m-d');
$created_by = $_SESSION['user_id'] ?? 1;

// Handle image upload
$image_path = null;
if ($image && !empty($image) && strpos($image, 'data:image') === 0) {
    $upload_dir = dirname(__DIR__, 2) . '/frontend/assets/uploads/projects/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $image_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image));
    $image_name = 'project_' . time() . '_' . uniqid() . '.png';
    $image_path = $image_name;
    file_put_contents($upload_dir . $image_name, $image_data);
}

$query = "INSERT INTO projects (name, client_name, amount, location, description, status, progress, department_id, image, start_date, created_by) 
          VALUES (:name, :client_name, :amount, :location, :description, :status, :progress, :department_id, :image, :start_date, :created_by)";
$stmt = $db->prepare($query);
$stmt->bindParam(':name', $name);
$stmt->bindParam(':client_name', $client_name);
$stmt->bindParam(':amount', $amount);
$stmt->bindParam(':location', $location);
$stmt->bindParam(':description', $description);
$stmt->bindParam(':status', $status);
$stmt->bindParam(':progress', $progress);
$stmt->bindParam(':department_id', $department_id);
$stmt->bindParam(':image', $image_path);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':created_by', $created_by);

if ($stmt->execute()) {
    sendResponse(true, ['id' => $db->lastInsertId()], "Project added successfully");
} else {
    sendResponse(false, null, "Failed to add project");
}
?>