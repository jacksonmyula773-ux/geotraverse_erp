<?php
// backend/api/add_project.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'));

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

if (empty($data->name)) {
    echo json_encode(['success' => false, 'message' => 'Project name required']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$user_dept = $_SESSION['department_id'];
$user_role = $_SESSION['role'];

// Determine department_id
if ($user_dept == 1 || $user_role == 'Super Administrator') {
    $dept_id = isset($data->department_id) ? $data->department_id : null;
} else {
    $dept_id = $user_dept;
}

$name = $data->name;
$client_name = $data->client_name ?? '';
$amount = $data->amount ?? 0;
$location = $data->location ?? '';
$description = $data->description ?? '';
$status = $data->status ?? 'pending';
$progress = $data->progress ?? 0;
$start_date = $data->start_date ?? date('Y-m-d');
$end_date = $data->end_date ?? null;

$query = "INSERT INTO projects (name, client_name, amount, location, description, status, progress, start_date, end_date, department_id, created_at) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = $db->prepare($query);

if ($stmt->execute([$name, $client_name, $amount, $location, $description, $status, $progress, $start_date, $end_date, $dept_id])) {
    $newId = $db->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Project added successfully',
        'data' => ['id' => $newId]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add project']);
}
?>