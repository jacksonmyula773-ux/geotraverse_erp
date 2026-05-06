<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$title = isset($_POST['title']) ? $_POST['title'] : null;

if (!$title) {
    echo json_encode(['success' => false, 'error' => 'Title required']);
    exit();
}

if (!isset($_FILES['report_file']) || $_FILES['report_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'File required']);
    exit();
}

$file = $_FILES['report_file'];
$allowedTypes = ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/msword', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
$maxSize = 10 * 1024 * 1024;

if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type']);
    exit();
}

if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'error' => 'File too large']);
    exit();
}

$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/geotraverse/frontend/assets/uploads/reports/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'report_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
$filepath = $uploadDir . $filename;
$webPath = '/geotraverse/frontend/assets/uploads/reports/' . $filename;

if (move_uploaded_file($file['tmp_name'], $filepath)) {
    $query = "INSERT INTO uploaded_reports (title, department_id, file_path, file_name, description, uploaded_by, created_at) 
              VALUES (:title, :dept_id, :file_path, :file_name, :desc, :user_id, NOW())";
    $stmt = $db->prepare($query);
    $dept_id = 1;
    $user_id = 1;
    $desc = isset($_POST['description']) ? $_POST['description'] : '';
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':dept_id', $dept_id);
    $stmt->bindParam(':file_path', $webPath);
    $stmt->bindParam(':file_name', $filename);
    $stmt->bindParam(':desc', $desc);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $content = "Uploaded file: " . $file['name'] . "\nSize: " . round($file['size'] / 1024, 2) . " KB\nDescription: " . $desc;
    $reportQuery = "INSERT INTO reports (department_id, title, period, content, status, created_at) VALUES (:dept_id, :title, 'uploaded', :content, 'unread', NOW())";
    $reportStmt = $db->prepare($reportQuery);
    $reportStmt->bindParam(':dept_id', $dept_id);
    $reportStmt->bindParam(':title', $title);
    $reportStmt->bindParam(':content', $content);
    $reportStmt->execute();
    
    echo json_encode(['success' => true, 'data' => ['file_path' => $webPath]]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to upload file']);
}
?>