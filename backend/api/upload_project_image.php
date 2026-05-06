<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$project_id = isset($_POST['project_id']) ? $_POST['project_id'] : null;

if (!$project_id) {
    echo json_encode(['success' => false, 'error' => 'Project ID required']);
    exit();
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Image file required']);
    exit();
}

$file = $_FILES['image'];
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxSize = 5 * 1024 * 1024;

if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Allowed: JPG, PNG, GIF, WEBP']);
    exit();
}

if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'error' => 'File too large. Max 5MB']);
    exit();
}

$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/geotraverse/frontend/assets/uploads/projects/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'project_' . $project_id . '_' . time() . '.' . $extension;
$filepath = $uploadDir . $filename;
$webPath = '/geotraverse/frontend/assets/uploads/projects/' . $filename;

if (move_uploaded_file($file['tmp_name'], $filepath)) {
    $query = "UPDATE projects SET image = :image WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':image', $webPath);
    $stmt->bindParam(':id', $project_id);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'data' => ['image_path' => $webPath]]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to upload image']);
}
?>