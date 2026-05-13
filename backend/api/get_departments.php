<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit();
}

$query = "SELECT id, name, email, phone, description FROM departments ORDER BY id";
$stmt = $pdo->prepare($query);
$stmt->execute();

$departments = array();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $departments[] = array(
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'email' => $row['email'],
        'phone' => $row['phone'],
        'description' => $row['description']
    );
}

echo json_encode(["success" => true, "data" => $departments]);
exit();
?>