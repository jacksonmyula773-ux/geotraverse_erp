<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->id)) {
    echo json_encode(['success' => false, 'error' => 'Employee ID required']);
    exit();
}

$fields = [];
$params = [':id' => $data->id];

if (isset($data->name)) { $fields[] = "name = :name"; $params[':name'] = $data->name; }
if (isset($data->email)) { $fields[] = "email = :email"; $params[':email'] = $data->email; }
if (isset($data->phone)) { $fields[] = "phone = :phone"; $params[':phone'] = $data->phone; }
if (isset($data->department_id)) { $fields[] = "department_id = :dept_id"; $params[':dept_id'] = $data->department_id; }
if (isset($data->role)) { $fields[] = "role = :role"; $params[':role'] = $data->role; }
if (isset($data->salary)) { $fields[] = "salary = :salary"; $params[':salary'] = $data->salary; }
if (isset($data->password) && !empty($data->password)) {
    $fields[] = "password = :password";
    $params[':password'] = password_hash($data->password, PASSWORD_DEFAULT);
}

if (empty($fields)) {
    echo json_encode(['success' => false, 'error' => 'No fields to update']);
    exit();
}

$query = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = :id";
$stmt = $db->prepare($query);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update employee']);
}
?>