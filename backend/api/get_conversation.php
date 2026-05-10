<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

require_once '../config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;
$current_dept = isset($_SESSION['department_id']) ? $_SESSION['department_id'] : 1;

if (!$department_id) {
    echo json_encode(['success' => false, 'message' => 'Department ID required']);
    exit;
}

$participant_1 = min($current_dept, $department_id);
$participant_2 = max($current_dept, $department_id);

$stmt = $conn->prepare("
    SELECT c.id as conversation_id, 
           m.*,
           d1.name as sender_name,
           d2.name as receiver_name
    FROM conversations c
    LEFT JOIN messages m ON c.id = m.conversation_id
    LEFT JOIN departments d1 ON m.sender_dept = d1.id
    LEFT JOIN departments d2 ON m.receiver_dept = d2.id
    WHERE c.participant_1 = ? AND c.participant_2 = ?
    ORDER BY m.created_at ASC
");
$stmt->bind_param("ii", $participant_1, $participant_2);
$stmt->execute();
$result = $stmt->get_result();
$messages = [];

while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

// Get department name
$deptStmt = $conn->prepare("SELECT name FROM departments WHERE id = ?");
$deptStmt->bind_param("i", $department_id);
$deptStmt->execute();
$deptResult = $deptStmt->get_result();
$deptName = $deptResult->fetch_assoc()['name'] ?? 'Unknown';

echo json_encode([
    'success' => true, 
    'data' => $messages,
    'department_name' => $deptName
]);
?>