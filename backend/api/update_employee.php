<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$host = "localhost";
$db_name = "geotraverse_erp";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=" . $host . ";dbname=" . $db_name . ";charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database connection failed: " . $e->getMessage()]);
    exit();
}

$input = json_decode(file_get_contents("php://input"), true);
if (!$input) {
    echo json_encode(["success" => false, "message" => "Invalid request data"]);
    exit();
}

$id = isset($input['id']) ? intval($input['id']) : 0;
$name = isset($input['name']) ? $input['name'] : '';
$email = isset($input['email']) ? $input['email'] : '';
$phone = isset($input['phone']) ? $input['phone'] : '';
$department_id = isset($input['department_id']) ? intval($input['department_id']) : 1;
$role = isset($input['role']) ? $input['role'] : 'Staff';
$salary = isset($input['salary']) ? floatval($input['salary']) : 0;
$password = isset($input['password']) ? $input['password'] : '';

if ($id === 0 || empty($name) || empty($email)) {
    echo json_encode(["success" => false, "message" => "Name and email required"]);
    exit();
}

if (!empty($password)) {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $update = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, department_id = ?, role = ?, salary = ?, password = ? WHERE id = ?");
    $update->execute([$name, $email, $phone, $department_id, $role, $salary, $hashedPassword, $id]);
} else {
    $update = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, department_id = ?, role = ?, salary = ? WHERE id = ?");
    $update->execute([$name, $email, $phone, $department_id, $role, $salary, $id]);
}

echo json_encode(["success" => true, "message" => "Employee updated successfully"]);
?>