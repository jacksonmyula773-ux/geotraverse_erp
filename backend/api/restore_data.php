<?php
// backend/api/restore_data.php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, null, "POST method required");
}

if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
    sendResponse(false, null, "Backup file required");
}

$fileContent = file_get_contents($_FILES['backup_file']['tmp_name']);
$backup = json_decode($fileContent, true);

if (!$backup) {
    sendResponse(false, null, "Invalid backup file");
}

$database = new Database();
$db = $database->getConnection();

// Start transaction
$db->beginTransaction();

try {
    // Truncate tables first
    $tables = ['budget_allocations', 'conversations', 'daily_work', 'messages', 'projects', 'reports', 'transactions', 'users'];
    foreach ($tables as $table) {
        $db->exec("TRUNCATE TABLE $table");
    }
    
    // Restore data
    foreach ($tables as $table) {
        if (isset($backup[$table]) && is_array($backup[$table])) {
            foreach ($backup[$table] as $row) {
                $columns = implode(", ", array_keys($row));
                $placeholders = ":" . implode(", :", array_keys($row));
                $query = "INSERT INTO $table ($columns) VALUES ($placeholders)";
                $stmt = $db->prepare($query);
                foreach ($row as $key => $value) {
                    $stmt->bindValue(":$key", $value);
                }
                $stmt->execute();
            }
        }
    }
    
    $db->commit();
    sendResponse(true, null, "Database restored successfully");
} catch (Exception $e) {
    $db->rollBack();
    sendResponse(false, null, "Restore failed: " . $e->getMessage());
}
?>