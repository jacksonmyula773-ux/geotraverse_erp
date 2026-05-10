<?php
// backend/api/send_report_to_dept.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';
require_once '../includes/auth.php';

validateSession();

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

if (!isset($data['report_id']) || !isset($data['to_department_id'])) {
    echo json_encode(['success' => false, 'message' => 'Report ID and Department ID required']);
    exit;
}

$report_id = intval($data['report_id']);
$to_dept_id = intval($data['to_department_id']);
$sender_dept_id = isset($data['from_department_id']) ? intval($data['from_department_id']) : $_SESSION['department_id'];

$database = new Database();
$db = $database->getConnection();

// Get the report details
$getReportQuery = "SELECT * FROM reports WHERE id = :id";
$stmt = $db->prepare($getReportQuery);
$stmt->bindParam(':id', $report_id);
$stmt->execute();
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    echo json_encode(['success' => false, 'message' => 'Report not found']);
    exit;
}

// Insert a copy to target department
$insertQuery = "INSERT INTO reports (title, period, content, status, department_id, created_at, is_viewed_by_admin, sent_from_dept) 
                VALUES (:title, :period, :content, 'sent', :dept_id, NOW(), 0, :sent_from)";
$insertStmt = $db->prepare($insertQuery);
$insertStmt->bindParam(':title', $report['title']);
$insertStmt->bindParam(':period', $report['period']);
$insertStmt->bindParam(':content', $report['content']);
$insertStmt->bindParam(':dept_id', $to_dept_id);
$insertStmt->bindParam(':sent_from', $sender_dept_id);

if ($insertStmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Report sent successfully'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send report']);
}
?>