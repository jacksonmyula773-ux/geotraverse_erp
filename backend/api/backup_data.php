<?php
// backend/api/backup_data.php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$backup = [];

// Get all tables
$tables = ['budget_allocations', 'conversations', 'daily_work', 'departments', 'messages', 'projects', 'reports', 'transactions', 'users'];

foreach ($tables as $table) {
    $query = "SELECT * FROM $table";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $backup[$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$backup['timestamp'] = date('Y-m-d H:i:s');

header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="geotraverse_backup_' . date('Y-m-d') . '.json"');
echo json_encode($backup, JSON_PRETTY_PRINT);
?>