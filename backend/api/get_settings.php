<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../includes/auth.php';
require_once '../config/database.php';

require_permission('view_all');

$result = $conn->query("SELECT * FROM settings WHERE id = 1");
$settings = $result->fetch_assoc();

echo json_encode(['success' => true, 'data' => $settings]);
?>