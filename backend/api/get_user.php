<?php
// backend/api/get_user.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$host = "localhost";
$db_name = "geotraverse_erp";
$username = "root";
$password = "";

try {
    $db = new PDO("mysql:host=" . $host . ";dbname=" . $db_name . ";charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit();
}

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id === 0) {
    echo json_encode(["success" => false, "message" => "Missing user_id"]);
    exit();
}

$query = $db->prepare("SELECT id, name, email, department_id, role FROM users WHERE id = ? AND is_active = 1");
$query->execute([$user_id]);
$user = $query->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo json_encode(["success" => true, "data" => $user]);
} else {
    echo json_encode(["success" => false, "message" => "User not found"]);
}
?>