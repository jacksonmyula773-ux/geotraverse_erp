<?php
/**
 * Record Payment for Daily Work
 * Method: POST
 * Body: {
 *   "daily_work_id": 1,
 *   "amount": 500000
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

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->daily_work_id) || !isset($data->amount)) {
    sendResponse(false, null, "Daily work ID and amount required");
}

$id = $data->daily_work_id;
$paymentAmount = floatval($data->amount);

if ($paymentAmount <= 0) {
    sendResponse(false, null, "Payment amount must be greater than 0");
}

// Get current record
$query = "SELECT * FROM daily_work WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$work = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$work) {
    sendResponse(false, null, "Daily work record not found");
}

// Check permission
if ($user['role'] !== 'Super Admin' && $work['department_id'] != $user['department_id']) {
    sendResponse(false, null, "You can only record payments for your own department", 403);
}

// Calculate new paid amount and remaining
$newPaid = $work['paid_amount'] + $paymentAmount;
$newRemaining = $work['income'] - $newPaid;

if ($newRemaining <= 0) {
    $newStatus = 'paid';
    $newPaid = $work['income'];
    $newRemaining = 0;
} else {
    $newStatus = 'partial';
}

$update = "UPDATE daily_work 
           SET paid_amount = :paid, remaining = :remaining, status = :status, updated_at = NOW() 
           WHERE id = :id";

$stmt2 = $db->prepare($update);
$stmt2->bindParam(':paid', $newPaid);
$stmt2->bindParam(':remaining', $newRemaining);
$stmt2->bindParam(':status', $newStatus);
$stmt2->bindParam(':id', $id);

if ($stmt2->execute()) {
    logActivity($user['id'], "Recorded payment", "Work ID: $id, Amount: $paymentAmount, New Status: $newStatus");
    sendResponse(true, [
        'new_paid' => $newPaid,
        'new_remaining' => $newRemaining,
        'new_status' => $newStatus
    ], "Payment recorded successfully");
} else {
    sendResponse(false, null, "Failed to record payment");
}
?>