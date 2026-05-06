<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? $_GET['id'] : null;

if ($id) {
    $query = "SELECT * FROM projects WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
} else {
    $query = "SELECT * FROM projects ORDER BY id DESC";
    $stmt = $db->prepare($query);
}

$stmt->execute();
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'data' => $projects]);
?>