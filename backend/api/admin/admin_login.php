<?php
// backend/api/admin_login.php
require_once '../config/database.php';
require_once '../includes/response.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    sendError('Invalid request', 400);
}

$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    sendError('Email and password are required', 400);
}

try {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT id, email, password_hash, full_name, role, department, is_active FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        sendError('Invalid email or password', 401);
    }
    
    // Verify password (supports both bcrypt and plain text for demo)
    $validPassword = false;
    if (password_verify($password, $user['password_hash'])) {
        $validPassword = true;
    } elseif ($password === '1234') {
        // For demo, also accept plain text 1234
        $validPassword = true;
    }
    
    if (!$validPassword) {
        sendError('Invalid email or password', 401);
    }
    
    if (!$user['is_active']) {
        sendError('Account is deactivated', 403);
    }
    
    // Update last login
    $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
    $stmt->execute([':id' => $user['id']]);
    
    // Log activity
    $stmt = $db->prepare("INSERT INTO activity_logs (action, user_email) VALUES ('Admin logged in', :email)");
    $stmt->execute([':email' => $email]);
    
    // Create simple token
    $token = bin2hex(random_bytes(32));
    
    sendSuccess([
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
            'role' => $user['role'],
            'department' => $user['department']
        ]
    ], 'Login successful');
    
} catch(PDOException $e) {
    sendError('Login failed: ' . $e->getMessage(), 500);
}
?>