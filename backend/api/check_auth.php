<?php
// C:\xampp\htdocs\geotraverse\backend\api\check_auth.php

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check if user is logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    echo json_encode([
        'logged_in' => true,
        'user_role' => $_SESSION['user_role'] ?? 'admin',
        'user_email' => $_SESSION['user_email'] ?? '',
        'user_name' => $_SESSION['user_name'] ?? 'Super Admin'
    ]);
} else {
    // For testing without login - set demo session
    // Remove this section in production
    if (!isset($_SESSION['logged_in'])) {
        $_SESSION['logged_in'] = true;
        $_SESSION['user_role'] = 'admin';
        $_SESSION['user_email'] = 'admin@geotraverse.com';
        $_SESSION['user_name'] = 'Super Admin';
    }
    echo json_encode([
        'logged_in' => true,
        'user_role' => 'admin',
        'user_email' => 'admin@geotraverse.com',
        'user_name' => 'Super Admin'
    ]);
}
?>