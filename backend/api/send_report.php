<?php
// backend/api/send_report.php
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

if (!$data || empty($data->report_id) || empty($data->to_department_id)) {
    echo json_encode(['success' => false, 'message' => 'Report ID and destination required']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$report_id = (int)$data->report_id;
$to_dept = (int)$data->to_department_id;
$message = $data->message ?? '';
$from_dept = $_SESSION['department_id'];

// Get report details
$reportQuery = "SELECT title, period, content FROM reports WHERE id = ?";
$reportStmt = $db->prepare($reportQuery);
$reportStmt->execute([$report_id]);
$report = $reportStmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    echo json_encode(['success' => false, 'message' => 'Report not found']);
    exit();
}

// Create message content
$fullMessage = "REPORT SHARED:\n";
$fullMessage .= "Title: " . $report['title'] . "\n";
$fullMessage .= "Period: " . $report['period'] . "\n";
$fullMessage .= "Content:\n" . $report['content'] . "\n";
if ($message) {
    $fullMessage .= "\nMessage from sender: " . $message;
}

// Insert message
$msgQuery = "INSERT INTO messages (from_department_id, to_department_id, message, created_at) VALUES (?, ?, ?, NOW())";
$msgStmt = $db->prepare($msgQuery);

if ($msgStmt->execute([$from_dept, $to_dept, $fullMessage])) {
    // Update report status to sent
    $updateQuery = "UPDATE reports SET status = 'sent' WHERE id = ?";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->execute([$report_id]);
    
    echo json_encode(['success' => true, 'message' => 'Report sent successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send report']);
}
?>