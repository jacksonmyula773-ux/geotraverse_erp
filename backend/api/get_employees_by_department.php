<?php
// backend/api/get_employees_by_department.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

require_once "../config/database.php";

$database = new Database();
$db = $database->getConnection();

$query = "SELECT d.id as department_id, d.name as department_name, 
          COUNT(u.id) as employee_count,
          GROUP_CONCAT(u.name SEPARATOR ', ') as employee_names
          FROM departments d
          LEFT JOIN users u ON d.id = u.department_id AND u.is_active = 1
          WHERE d.id != 1
          GROUP BY d.id
          ORDER BY d.name";

$stmt = $db->prepare($query);
$stmt->execute();

$departments = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $departments[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $departments
]);
?>