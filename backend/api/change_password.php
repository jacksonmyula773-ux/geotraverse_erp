<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->email) || !isset($data->current_password) || !isset($data->new_password)) {
    echo json_encode(['success' => false, 'error' => 'Email, current password and new password required']);
    exit();
}

$email = $data->email;
$current = $data->current_password;
$new = $data->new_password;

// Check in users table for admin
$query = "SELECT id, password FROM users WHERE email = :email";
$stmt = $db->prepare($query);
$stmt->bindParam(':email', $email);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (password_verify($current, $user['password'])) {
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $update = "UPDATE users SET password = :newpass WHERE id = :id";
        $stmt2 = $db->prepare($update);
        $stmt2->bindParam(':newpass', $newHash);
        $stmt2->bindParam(':id', $user['id']);
        if ($stmt2->execute()) {
            echo json_encode(['success' => true]);
            exit();
        }
    }
}

// Check in departments table
$query = "SELECT id, password FROM departments WHERE email = :email";
$stmt = $db->prepare($query);
$stmt->bindParam(':email', $email);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (password_verify($current, $user['password'])) {
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $update = "UPDATE departments SET password = :newpass WHERE id = :id";
        $stmt2 = $db->prepare($update);
        $stmt2->bindParam(':newpass', $newHash);
        $stmt2->bindParam(':id', $user['id']);
        if ($stmt2->execute()) {
            echo json_encode(['success' => true]);
            exit();
        }
    }
}

echo json_encode(['success' => false, 'error' => 'Current password is incorrect']);
?>