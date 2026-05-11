<?php
// backend/api/get_employees.php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$query = "SELECT u.id, u.name, u.email, u.phone, u.department_id, u.role, u.salary, u.join_date, u.is_active, u.bio,
          d.name as department_name
          FROM users u
          LEFT JOIN departments d ON u.department_id = d.id
          ORDER BY u.id DESC";
$stmt = $db->prepare($query);
$stmt->execute();

$employees = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $employees[] = $row;
}

sendResponse(true, $employees);
?>