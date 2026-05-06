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

if (!isset($data->id)) {
    echo json_encode(['success' => false, 'error' => 'Project ID required']);
    exit();
}

$fields = [];
$params = [':id' => $data->id];

if (isset($data->name)) { $fields[] = "name = :name"; $params[':name'] = $data->name; }
if (isset($data->client_name)) { $fields[] = "client_name = :client"; $params[':client'] = $data->client_name; }
if (isset($data->amount)) { $fields[] = "amount = :amount"; $params[':amount'] = $data->amount; }
if (isset($data->status)) { $fields[] = "status = :status"; $params[':status'] = $data->status; }
if (isset($data->progress)) { $fields[] = "progress = :progress"; $params[':progress'] = $data->progress; }
if (isset($data->location)) { $fields[] = "location = :location"; $params[':location'] = $data->location; }
if (isset($data->description)) { $fields[] = "description = :desc"; $params[':desc'] = $data->description; }
if (isset($data->image)) { $fields[] = "image = :image"; $params[':image'] = $data->image; }

$fields[] = "updated_at = NOW()";

$query = "UPDATE projects SET " . implode(", ", $fields) . " WHERE id = :id";
$stmt = $db->prepare($query);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update project']);
}
?>