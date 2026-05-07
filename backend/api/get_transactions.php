<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../includes/auth.php';
require_once '../config/database.php';

require_permission('view_transactions');

$dept_id = get_current_department_id();

if ($dept_id == DEPT_SUPER_ADMIN || $dept_id == DEPT_FINANCE) {
    $result = $conn->query("
        SELECT t.*, d.name as department_name
        FROM transactions t
        LEFT JOIN departments d ON t.department_id = d.id
        ORDER BY t.created_at DESC
    ");
} else {
    $stmt = $conn->prepare("
        SELECT t.*, d.name as department_name
        FROM transactions t
        LEFT JOIN departments d ON t.department_id = d.id
        WHERE t.department_id = ?
        ORDER BY t.created_at DESC
    ");
    $stmt->bind_param("i", $dept_id);
    $stmt->execute();
    $result = $stmt->get_result();
}

$transactions = $result->fetch_all(MYSQLI_ASSOC);
echo json_encode(['success' => true, 'data' => $transactions]);
?>