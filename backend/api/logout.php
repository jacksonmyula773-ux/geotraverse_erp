<?php
// C:\xampp\htdocs\geotraverse\backend\api\logout.php

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Destroy session
session_destroy();

echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
?>