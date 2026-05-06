<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->name)) {
    echo json_encode(['success' => false, 'error' => 'Project name required']);
    exit();
}

$query = "INSERT INTO projects (name, client_name, amount, status, progress, location, description, department_id, created_at, updated_at) 
          VALUES (:name, :client, :amount, :status, :progress, :location, :desc, :dept_id, NOW(), NOW())";

$stmt = $db->prepare($query);
$stmt->bindParam(':name', $data->name);
$stmt->bindParam(':client', $data->client_name);
$stmt->bindParam(':amount', $data->amount);
$status = $data->status ?? 'pending';
$stmt->bindParam(':status', $status);
$progress = $data->progress ?? 0;
$stmt->bindParam(':progress', $progress);
$stmt->bindParam(':location', $data->location);
$stmt->bindParam(':desc', $data->description);
$dept_id = $data->department_id ?? 1;
$stmt->bindParam(':dept_id', $dept_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'data' => ['id' => $db->lastInsertId()]]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to add project']);
}
?>