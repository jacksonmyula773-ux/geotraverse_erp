<?php
/**
 * Add Daily Work Record
 * Method: POST
 * Body: {
 *   "date": "2024-05-05",
 *   "project_name": "Project Name",
 *   "work_description": "Description",
 *   "income": 1000000,
 *   "expenses": 500000,
 *   "paid_amount": 500000,
 *   "status": "paid|partial|pending"
 * }
 */

require_once '../config/database.php';
require_once '../includes/auth.php';

$auth = new Auth();
$user = $auth->validateToken();

if (!$user) {
    sendResponse(false, null, "Unauthorized", 401);
}

$database = new Database();
$db = $database->getConnection();

// Get JSON input
$data = json_decode(file_get_contents("php://input"));

// Validate required fields
if (!isset($data->date) || !isset($data->project_name) || !isset($data->work_description)) {
    sendResponse(false, null, "Date, project name and work description are required");
}

// Sanitize inputs
$date = $data->date;
$project_name = trim($data->project_name);
$work_description = trim($data->work_description);
$income = isset($data->income) ? floatval($data->income) : 0;
$expenses = isset($data->expenses) ? floatval($data->expenses) : 0;
$paid_amount = isset($data->paid_amount) ? floatval($data->paid_amount) : 0;
$status = isset($data->status) ? $data->status : 'pending';

// Validate status
$allowedStatus = ['paid', 'partial', 'pending'];
if (!in_array($status, $allowedStatus)) {
    sendResponse(false, null, "Invalid status. Allowed: paid, partial, pending");
}

// If status is paid, paid_amount should equal income
if ($status === 'paid') {
    $paid_amount = $income;
}

// Calculate remaining
$remaining = $income - $paid_amount;

// Calculate profit
$profit = $income - $expenses;

// Insert into database
$query = "INSERT INTO daily_work (department_id, date, project_name, work_description, 
          income, expenses, paid_amount, remaining, profit, status, created_at, updated_at) 
          VALUES (:dept_id, :date, :project_name, :work_description, 
          :income, :expenses, :paid_amount, :remaining, :profit, :status, NOW(), NOW())";

$stmt = $db->prepare($query);
$stmt->bindParam(':dept_id', $user['department_id']);
$stmt->bindParam(':date', $date);
$stmt->bindParam(':project_name', $project_name);
$stmt->bindParam(':work_description', $work_description);
$stmt->bindParam(':income', $income);
$stmt->bindParam(':expenses', $expenses);
$stmt->bindParam(':paid_amount', $paid_amount);
$stmt->bindParam(':remaining', $remaining);
$stmt->bindParam(':profit', $profit);
$stmt->bindParam(':status', $status);

if ($stmt->execute()) {
    $id = $db->lastInsertId();
    
    // Log activity
    logActivity($user['id'], "Added daily work record", "Project: $project_name, Income: $income");
    
    sendResponse(true, [
        'id' => $id,
        'date' => $date,
        'project_name' => $project_name,
        'income' => $income,
        'expenses' => $expenses,
        'profit' => $profit,
        'paid_amount' => $paid_amount,
        'remaining' => $remaining,
        'status' => $status
    ], "Daily work added successfully");
} else {
    sendResponse(false, null, "Failed to add daily work record");
}
?>