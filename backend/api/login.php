<?php
// backend/api/login.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'));

if (!isset($data->email) || !isset($data->password)) {
    echo '{"success":false,"message":"Email and password required"}';
    exit();
}

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo '{"success":false,"message":"Database connection failed"}';
    exit();
}

$email = $data->email;
$password = md5($data->password);

$query = "SELECT u.*, d.name as department_name 
          FROM users u 
          LEFT JOIN departments d ON u.department_id = d.id 
          WHERE u.email = ? AND u.password = ? AND u.is_active = 1";

$stmt = $db->prepare($query);
$stmt->execute([$email, $password]);

if ($stmt->rowCount() > 0) {
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['department_id'] = $user['department_id'];
    $_SESSION['department_name'] = $user['department_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['logged_in'] = true;
    
    // Determine redirect page based on department
    $departmentPages = [
        2 => 'finance.html', 3 => 'sales_marketing.html', 4 => 'manager.html',
        5 => 'secretary.html', 6 => 'bricks_timber.html', 7 => 'aluminium.html',
        8 => 'town_planning.html', 9 => 'architectural.html', 10 => 'survey.html',
        11 => 'construction.html', 12 => 'hatimiliki.html'
    ];
    
    $redirect = ($user['department_id'] == 1) ? 'super_admin.html' : ($departmentPages[$user['department_id']] ?? 'dashboard.html');
    
    unset($user['password']);
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user' => $user,
        'redirect' => $redirect
    ]);
} else {
    echo '{"success":false,"message":"Invalid email or password"}';
}
?>