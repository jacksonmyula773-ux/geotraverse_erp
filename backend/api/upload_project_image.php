<?php
// backend/api/upload_project_image.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if (!isset($_FILES['image']) || !isset($_POST['project_id'])) {
    echo json_encode(['success' => false, 'message' => 'Image and project ID required']);
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$project_id = (int)$_POST['project_id'];
$upload_dir = '../../frontend/uploads/projects/';

if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
$file_name = 'project_' . $project_id . '_' . time() . '.' . $file_extension;
$file_path = $upload_dir . $file_name;

if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
    $db_path = 'uploads/projects/' . $file_name;
    $updateQuery = "UPDATE projects SET image = ? WHERE id = ?";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->execute([$db_path, $project_id]);
    
    echo json_encode(['success' => true, 'message' => 'Image uploaded', 'path' => $db_path]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
}
?>