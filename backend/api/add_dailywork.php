<?php
// backend/api/add_dailywork.php
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

// Validate required fields
if (empty($data->date)) {
    echo json_encode(['success' => false, 'message' => 'Date is required']);
    exit();
}

if (empty($data->project_name)) {
    echo json_encode(['success' => false, 'message' => 'Project name is required']);
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

$date = $data->date;
$project_name = $data->project_name;
$work_description = $data->work_description ?? '';
$income = $data->income ?? 0;
$expenses = $data->expenses ?? 0;
$paid_amount = $data->paid_amount ?? 0;
$status = $data->status ?? 'pending';

// Calculate remaining and profit
$remaining = $income - $paid_amount;
$profit = $income - $expenses;

// If status is paid, set paid_amount = income
if ($status == 'paid') {
    $paid_amount = $income;
    $remaining = 0;
}

$query = "INSERT INTO daily_work (date, project_name, work_description, income, expenses, paid_amount, status, department_id, created_at) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = $db->prepare($query);

if ($stmt->execute([$date, $project_name, $work_description, $income, $expenses, $paid_amount, $status, $dept_id])) {
    $newId = $db->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Daily work added successfully',
        'data' => ['id' => $newId]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add daily work']);
}
?>