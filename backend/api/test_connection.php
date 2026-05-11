<?php
// C:\xampp\htdocs\geotraverse\backend\api\test_connection.php
error_reporting(1);
ini_set('display_errors', 1);

header('Content-Type: application/json');

require_once 'db_connect.php';

$result = $conn->query("SELECT COUNT(*) as cnt FROM users");

if ($result) {
    $row = $result->fetch_assoc();
    echo json_encode([
        'success' => true, 
        'message' => 'Database connected successfully',
        'users_count' => $row['cnt']
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Query failed: ' . $conn->error
    ]);
}

$conn->close();
?>