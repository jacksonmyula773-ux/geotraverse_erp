<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

require_once '../config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$upload_dir = dirname(__DIR__, 2) . '/frontend/assets/uploads/reports/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$response = ['success' => false, 'message' => 'No file uploaded'];

if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['report_file'];
    $original_name = $file['name'];
    $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);
    $new_filename = time() . '_' . rand(1000, 9999) . '.' . $file_extension;
    $file_path = $upload_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        $title = isset($_POST['title']) ? $_POST['title'] : '';
        $period = isset($_POST['period']) ? $_POST['period'] : 'monthly';
        $department_id = isset($_POST['department_id']) ? intval($_POST['department_id']) : 1;
        $content = '[File uploaded: ' . $new_filename . ']\nOriginal file: ' . $original_name . '\nPlease click "View File" button to open this document.';
        
        $stmt = $conn->prepare("INSERT INTO reports (title, period, content, department_id, status, created_at) VALUES (?, ?, ?, ?, 'sent', NOW())");
        $stmt->bind_param("sssi", $title, $period, $content, $department_id);
        $stmt->execute();
        
        $report_id = $conn->insert_id;
        
        $response = [
            'success' => true, 
            'message' => 'File uploaded successfully',
            'report_id' => $report_id,
            'file_name' => $new_filename,
            'original_name' => $original_name
        ];
    } else {
        $response = ['success' => false, 'message' => 'Failed to move uploaded file'];
    }
} elseif (isset($_FILES['report_file']) && $_FILES['report_file']['error'] !== UPLOAD_ERR_OK) {
    $response = ['success' => false, 'message' => 'Upload error: ' . $_FILES['report_file']['error']];
}

echo json_encode($response);
?>