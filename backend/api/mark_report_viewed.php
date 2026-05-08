<?php
require_once 'db_connection.php';

$data = json_decode(file_get_contents('php://input'), true);
$reportId = isset($data['report_id']) ? intval($data['report_id']) : null;

if (!$reportId) {
    echo json_encode(['success' => false, 'message' => 'Report ID required']);
    exit;
}

$query = "UPDATE reports SET is_viewed_by_admin = 1 WHERE id = $reportId";

if ($conn->query($query)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to mark report as viewed']);
}

$conn->close();
?>