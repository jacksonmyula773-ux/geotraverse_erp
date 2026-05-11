<?php
// backend/api/test_api.php
header('Content-Type: application/json');
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$results = [];

// Test departments
$deptQuery = "SELECT COUNT(*) as cnt FROM departments";
$deptStmt = $db->prepare($deptQuery);
$deptStmt->execute();
$results['departments'] = $deptStmt->fetch(PDO::FETCH_ASSOC);

// Test messages
$msgQuery = "SELECT COUNT(*) as cnt FROM messages";
$msgStmt = $db->prepare($msgQuery);
$msgStmt->execute();
$results['messages'] = $msgStmt->fetch(PDO::FETCH_ASSOC);

// Test transactions
$transQuery = "SELECT COUNT(*) as cnt, type, status FROM transactions GROUP BY type, status";
$transStmt = $db->prepare("SELECT COUNT(*) as cnt FROM transactions");
$transStmt->execute();
$results['transactions'] = $transStmt->fetch(PDO::FETCH_ASSOC);

sendResponse(true, $results, "API test successful");
?>