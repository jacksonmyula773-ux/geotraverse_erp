<?php
// backend/api/login.php
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['email']) || !isset($data['password'])) {
    sendResponse(false, null, "Email and password required");
}

$database = new Database();
$db = $database->getConnection();

$query = "SELECT id, name, email, password, department_id, role, is_active FROM users WHERE email = :email";
$stmt = $db->prepare($query);
$stmt->bindParam(':email', $data['email']);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (password_verify($data['password'], $user['password'])) {
        if ($user['is_active'] != 1) {
            sendResponse(false, null, "Account is disabled");
        }
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['department_id'] = $user['department_id'];
        $_SESSION['role'] = $user['role'];
        
        unset($user['password']);
        sendResponse(true, $user, "Login successful");
    } else {
        sendResponse(false, null, "Invalid password");
    }
} else {
    sendResponse(false, null, "User not found");
}
?>