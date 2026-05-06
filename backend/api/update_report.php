<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->id) || !isset($data->title) || !isset($data->content)) {
    echo json_encode(['success' => false, 'error' => 'ID, title and content required']);
    exit();
}

$query = "UPDATE reports SET title = :title, content = :content, period = :period WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $data->id);
$stmt->bindParam(':title', $data->title);
$stmt->bindParam(':content', $data->content);
$stmt->bindParam(':period', $data->period);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update report']);
}
?>