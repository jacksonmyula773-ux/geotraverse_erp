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

$data = json_decode(file_get_contents('php://input'), true);

$id = $data['id'] ?? 0;
$name = $data['name'] ?? '';
$client_name = $data['client_name'] ?? '';
$amount = $data['amount'] ?? 0;
$location = $data['location'] ?? '';
$description = $data['description'] ?? '';
$status = $data['status'] ?? 'pending';
$progress = $data['progress'] ?? 0;
$department_id = $data['department_id'] ?? 1;
$image = $data['image'] ?? '';

if (!$id || !$name) {
    echo json_encode(['success' => false, 'message' => 'Project ID and name required']);
    exit;
}

$upload_dir = dirname(__DIR__, 2) . '/frontend/assets/uploads/projects/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$image_url = null;
if ($image && $image != '') {
    $filename = 'project_' . time() . '_' . rand(1000, 9999) . '.png';
    $filepath = $upload_dir . $filename;
    
    $image_data = preg_replace('#^data:image/\w+;base64,#i', '', $image);
    $image_data = base64_decode($image_data);
    
    if (file_put_contents($filepath, $image_data)) {
        $image_url = 'assets/uploads/projects/' . $filename;
    }
}

if ($image_url) {
    $stmt = $conn->prepare("UPDATE projects SET name = ?, client_name = ?, amount = ?, location = ?, description = ?, status = ?, progress = ?, department_id = ?, image = ? WHERE id = ?");
    $stmt->bind_param("ssdsssiisi", $name, $client_name, $amount, $location, $description, $status, $progress, $department_id, $image_url, $id);
} else {
    $stmt = $conn->prepare("UPDATE projects SET name = ?, client_name = ?, amount = ?, location = ?, description = ?, status = ?, progress = ?, department_id = ? WHERE id = ?");
    $stmt->bind_param("ssdsssiii", $name, $client_name, $amount, $location, $description, $status, $progress, $department_id, $id);
}
$stmt->execute();

echo json_encode(['success' => true]);
?>