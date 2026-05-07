<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../includes/auth.php';
require_once '../config/database.php';

require_permission('view_daily_work');

$dept_id = get_current_department_id();
$user_id = get_current_user_id();

if ($dept_id == DEPT_SUPER_ADMIN) {
    $result = $conn->query("
        SELECT d.*, dep.name as department_name, p.name as project_name
        FROM daily_work d
        LEFT JOIN departments dep ON d.department_id = dep.id
        LEFT JOIN projects p ON d.project_name = p.name
        ORDER BY d.date DESC
    ");
} else {
    $stmt = $conn->prepare("
        SELECT d.*, dep.name as department_name, p.name as project_name
        FROM daily_work d
        LEFT JOIN departments dep ON d.department_id = dep.id
        LEFT JOIN projects p ON d.project_name = p.name
        WHERE d.department_id = ?
        ORDER BY d.date DESC
    ");
    $stmt->bind_param("i", $dept_id);
    $stmt->execute();
    $result = $stmt->get_result();
}

$daily_work = $result->fetch_all(MYSQLI_ASSOC);
echo json_encode(['success' => true, 'data' => $daily_work]);
?>