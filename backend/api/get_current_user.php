<?php
// backend/api/get_current_user.php
session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");

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

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Not logged in"]);
    exit();
}

$user_id = $_SESSION['user_id'];

$query = $db->prepare("SELECT id, name, email, department_id, role, is_active FROM users WHERE id = ? AND is_active = 1");
$query->execute([$user_id]);
$user = $query->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo json_encode(["success" => true, "data" => $user]);
} else {
    echo json_encode(["success" => false, "message" => "User not found"]);
}
?>