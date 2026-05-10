<?php
// backend/api/send_project_to_dept.php
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

if (!isset($data['project_id']) || !isset($data['to_department_id'])) {
    echo json_encode(['success' => false, 'message' => 'Project ID and Department ID required']);
    exit;
}

$project_id = intval($data['project_id']);
$to_dept_id = intval($data['to_department_id']);
$sender_dept_id = isset($data['from_department_id']) ? intval($data['from_department_id']) : $_SESSION['department_id'];
$message = isset($data['message']) ? $data['message'] : '';

$database = new Database();
$db = $database->getConnection();

// Get the project details
$getProjectQuery = "SELECT * FROM projects WHERE id = :id";
$stmt = $db->prepare($getProjectQuery);
$stmt->bindParam(':id', $project_id);
$stmt->execute();
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    echo json_encode(['success' => false, 'message' => 'Project not found']);
    exit;
}

// Insert a copy to target department
$insertQuery = "INSERT INTO projects (name, client_name, amount, status, progress, location, description, image, start_date, end_date, department_id, created_at, is_viewed_by_admin, sent_from_dept) 
                VALUES (:name, :client_name, :amount, :status, :progress, :location, :description, :image, :start_date, :end_date, :dept_id, NOW(), 0, :sent_from)";
$insertStmt = $db->prepare($insertQuery);
$insertStmt->bindParam(':name', $project['name']);
$insertStmt->bindParam(':client_name', $project['client_name']);
$insertStmt->bindParam(':amount', $project['amount']);
$insertStmt->bindParam(':status', $project['status']);
$insertStmt->bindParam(':progress', $project['progress']);
$insertStmt->bindParam(':location', $project['location']);
$insertStmt->bindParam(':description', $project['description']);
$insertStmt->bindParam(':image', $project['image']);
$insertStmt->bindParam(':start_date', $project['start_date']);
$insertStmt->bindParam(':end_date', $project['end_date']);
$insertStmt->bindParam(':dept_id', $to_dept_id);
$insertStmt->bindParam(':sent_from', $sender_dept_id);

if ($insertStmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Project sent successfully'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send project']);
}
?>