<?php
// C:\xampp\htdocs\geotraverse\backend\api\login.php

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database connection parameters
$host = 'localhost';
$dbname = 'geotraverse_erp';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$password_input = $data['password'] ?? '';
$userType = $data['userType'] ?? 'admin';

if (empty($email) || empty($password_input)) {
    echo json_encode(['success' => false, 'error' => 'Email and password required']);
    exit();
}

if ($userType === 'admin') {
    // Admin login from users table
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password_input, $user['password'])) {
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = 'admin';
        
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => 'admin'
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
    }
} else {
    // Department login from departments table
    $stmt = $pdo->prepare("SELECT * FROM departments WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $dept = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($dept && password_verify($password_input, $dept['password'])) {
        $_SESSION['logged_in'] = true;
        $_SESSION['department_id'] = $dept['id'];
        $_SESSION['department_name'] = $dept['name'];
        $_SESSION['user_role'] = 'department';
        
        echo json_encode([
            'success' => true,
            'department' => [
                'id' => $dept['id'],
                'name' => $dept['name'],
                'email' => $dept['email']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
    }
}
?>