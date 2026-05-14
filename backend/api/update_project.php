<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
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

$project_id = isset($input['id']) ? intval($input['id']) : 0;
$name = isset($input['name']) ? trim($input['name']) : '';
$client_name = isset($input['client_name']) ? trim($input['client_name']) : '';
$amount = isset($input['amount']) ? floatval($input['amount']) : 0;
$location = isset($input['location']) ? trim($input['location']) : '';
$description = isset($input['description']) ? trim($input['description']) : '';
$status = isset($input['status']) ? $input['status'] : 'pending';
$progress = isset($input['progress']) ? intval($input['progress']) : 0;
$department_id = isset($input['department_id']) ? intval($input['department_id']) : 1;
$image = isset($input['image']) ? $input['image'] : '';

if ($project_id === 0 || empty($name)) {
    echo json_encode(["success" => false, "message" => "Project ID and name required"]);
    exit();
}

try {
    // Update project details
    $update = $pdo->prepare("UPDATE projects SET name = ?, client_name = ?, amount = ?, location = ?, description = ?, status = ?, progress = ?, department_id = ? WHERE id = ?");
    $update->execute([$name, $client_name, $amount, $location, $description, $status, $progress, $department_id, $project_id]);
    
    // Update image if provided (not empty)
    if (!empty($image) && $image !== 'null' && $image !== 'undefined') {
        // Check if image is base64 (new upload) or just a URL
        if (strpos($image, 'data:image') === 0) {
            // It's a base64 image - save it
            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image));
            $imageName = 'project_' . time() . '_' . rand(1000, 9999) . '.png';
            $uploadPath = '../../frontend/assets/uploads/projects/';
            
            // Create directory if not exists
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
            
            $imagePath = $uploadPath . $imageName;
            file_put_contents($imagePath, $imageData);
            
            // Save just the filename in database
            $updateImage = $pdo->prepare("UPDATE projects SET image = ? WHERE id = ?");
            $updateImage->execute([$imageName, $project_id]);
        } else if (strpos($image, '/') !== false || strpos($image, 'http') !== false) {
            // It's a URL or path - save as is
            $updateImage = $pdo->prepare("UPDATE projects SET image = ? WHERE id = ?");
            $updateImage->execute([$image, $project_id]);
        }
    }
    
    echo json_encode(["success" => true, "message" => "Project updated successfully"]);
} catch(PDOException $e) {
    echo json_encode(["success" => false, "message" => "Update failed: " . $e->getMessage()]);
}
?>