<?php
/**
 * Update Daily Work Record
 * Method: PUT
 * Parameters: id (in URL or body)
 * Body: fields to update
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

// Get ID from URL or body
$id = isset($_GET['id']) ? $_GET['id'] : null;
$data = json_decode(file_get_contents("php://input"));

if (!$id && isset($data->id)) {
    $id = $data->id;
}

if (!$id) {
    sendResponse(false, null, "Daily work ID required");
}

// First, check if record exists and belongs to this department
$checkQuery = "SELECT * FROM daily_work WHERE id = :id";
$checkStmt = $db->prepare($checkQuery);
$checkStmt->bindParam(':id', $id);
$checkStmt->execute();
$existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

if (!$existing) {
    sendResponse(false, null, "Daily work record not found");
}

// Super Admin can edit any, others only their own
if ($user['role'] !== 'Super Admin' && $existing['department_id'] != $user['department_id']) {
    sendResponse(false, null, "You can only edit your own department's records", 403);
}

// Build update query
$fields = [];
$params = [':id' => $id];

$updatableFields = ['date', 'project_name', 'work_description', 'income', 'expenses', 'paid_amount', 'status'];

foreach ($updatableFields as $field) {
    if (isset($data->$field)) {
        $fields[] = "$field = :$field";
        $params[":$field"] = $data->$field;
    }
}

if (empty($fields)) {
    sendResponse(false, null, "No fields to update");
}

// Recalculate remaining and profit if income, expenses, or paid_amount changed
if (isset($data->income) || isset($data->expenses) || isset($data->paid_amount) || isset($data->status)) {
    $income = isset($data->income) ? $data->income : $existing['income'];
    $expenses = isset($data->expenses) ? $data->expenses : $existing['expenses'];
    $paid_amount = isset($data->paid_amount) ? $data->paid_amount : $existing['paid_amount'];
    $status = isset($data->status) ? $data->status : $existing['status'];
    
    if ($status === 'paid') {
        $paid_amount = $income;
    }
    
    $remaining = $income - $paid_amount;
    $profit = $income - $expenses;
    
    $fields[] = "remaining = :remaining";
    $fields[] = "profit = :profit";
    $params[':remaining'] = $remaining;
    $params[':profit'] = $profit;
}

$fields[] = "updated_at = NOW()";

$query = "UPDATE daily_work SET " . implode(", ", $fields) . " WHERE id = :id";
$stmt = $db->prepare($query);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

if ($stmt->execute()) {
    logActivity($user['id'], "Updated daily work record", "ID: $id");
    sendResponse(true, null, "Daily work updated successfully");
} else {
    sendResponse(false, null, "Failed to update daily work");
}
?>