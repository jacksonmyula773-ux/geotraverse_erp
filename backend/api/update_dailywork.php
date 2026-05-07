<?php
// backend/api/update_dailywork.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'));

if (!$data || empty($data->id)) {
    echo json_encode(['success' => false, 'message' => 'Daily work ID required']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$id = (int)$data->id;
$user_dept = $_SESSION['department_id'];
$user_role = $_SESSION['role'];

// Check if daily work exists and get its department
$checkQuery = "SELECT department_id FROM daily_work WHERE id = ?";
$checkStmt = $db->prepare($checkQuery);
$checkStmt->execute([$id]);
$dailywork = $checkStmt->fetch(PDO::FETCH_ASSOC);

if (!$dailywork) {
    echo json_encode(['success' => false, 'message' => 'Daily work record not found']);
    exit();
}

// Check permission
if ($user_dept != 1 && $user_role != 'Super Administrator' && $dailywork['department_id'] != $user_dept) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

// Build update query
$updates = [];
$params = [];

if (isset($data->date)) {
    $updates[] = "date = ?";
    $params[] = $data->date;
}
if (isset($data->project_name)) {
    $updates[] = "project_name = ?";
    $params[] = $data->project_name;
}
if (isset($data->work_description)) {
    $updates[] = "work_description = ?";
    $params[] = $data->work_description;
}
if (isset($data->income)) {
    $updates[] = "income = ?";
    $params[] = $data->income;
}
if (isset($data->expenses)) {
    $updates[] = "expenses = ?";
    $params[] = $data->expenses;
}
if (isset($data->paid_amount)) {
    $updates[] = "paid_amount = ?";
    $params[] = $data->paid_amount;
}
if (isset($data->status)) {
    $updates[] = "status = ?";
    $params[] = $data->status;
    
    // If status is paid, set paid_amount = income
    if ($data->status == 'paid') {
        $incomeQuery = "SELECT income FROM daily_work WHERE id = ?";
        $incomeStmt = $db->prepare($incomeQuery);
        $incomeStmt->execute([$id]);
        $incomeData = $incomeStmt->fetch(PDO::FETCH_ASSOC);
        if ($incomeData) {
            $updates[] = "paid_amount = ?";
            $params[] = $incomeData['income'];
        }
    }
}

if (empty($updates)) {
    echo json_encode(['success' => false, 'message' => 'No fields to update']);
    exit();
}

$params[] = $id;
$query = "UPDATE daily_work SET " . implode(", ", $updates) . " WHERE id = ?";
$stmt = $db->prepare($query);

if ($stmt->execute($params)) {
    echo json_encode(['success' => true, 'message' => 'Daily work updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update daily work']);
}
?>