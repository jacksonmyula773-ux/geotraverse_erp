<?php
require_once 'db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if ($data && isset($data['image']) && isset($data['project_id'])) {
        $imageData = $data['image'];
        $projectId = intval($data['project_id']);
        
        // Extract base64 image
        if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $matches)) {
            $imageType = $matches[1];
            $imageData = substr($imageData, strpos($imageData, ',') + 1);
            $imageData = base64_decode($imageData);
            
            // Create upload directory
            $uploadDir = '../frontend/assets/uploads/projects/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = time() . '_' . $projectId . '.' . $imageType;
            $filePath = $uploadDir . $fileName;
            
            if (file_put_contents($filePath, $imageData)) {
                $imagePath = 'assets/uploads/projects/' . $fileName;
                $stmt = $conn->prepare("UPDATE projects SET image = ? WHERE id = ?");
                $stmt->bind_param("si", $imagePath, $projectId);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'path' => $imagePath]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Database update failed']);
                }
                $stmt->close();
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to save image file']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid image format']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No image data received']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>