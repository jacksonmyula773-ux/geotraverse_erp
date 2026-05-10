<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Super Administrator') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_GET['action']) ? $_GET['action'] : 'backup';
    
    if ($action === 'backup') {
        // Create backup of all tables
        $tables = ['users', 'departments', 'projects', 'transactions', 'daily_work', 'reports', 'messages', 'conversations', 'budget_allocations'];
        $backupData = [];
        
        foreach ($tables as $table) {
            $result = $conn->query("SELECT * FROM $table");
            $backupData[$table] = $result->fetch_all(MYSQLI_ASSOC);
        }
        
        $backupData['timestamp'] = date('Y-m-d H:i:s');
        $backupJson = json_encode($backupData, JSON_PRETTY_PRINT);
        
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.json';
        $filepath = '../assets/backups/' . $filename;
        
        if (!is_dir('../assets/backups')) mkdir('../assets/backups', 0777, true);
        file_put_contents($filepath, $backupJson);
        
        echo json_encode(['success' => true, 'filename' => $filename, 'filepath' => $filepath]);
        
    } elseif ($action === 'restore') {
        $data = json_decode(file_get_contents('php://input'), true);
        $backupFile = $data['backup_file'] ?? null;
        
        if (!$backupFile || !file_exists('../assets/backups/' . $backupFile)) {
            echo json_encode(['success' => false, 'message' => 'Backup file not found']);
            exit;
        }
        
        $backupData = json_decode(file_get_contents('../assets/backups/' . $backupFile), true);
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            $tables = ['messages', 'conversations', 'daily_work', 'transactions', 'reports', 'projects', 'users', 'departments'];
            
            foreach ($tables as $table) {
                if (isset($backupData[$table])) {
                    $conn->query("TRUNCATE TABLE $table");
                    foreach ($backupData[$table] as $row) {
                        $columns = implode(',', array_keys($row));
                        $values = "'" . implode("','", array_map([$conn, 'real_escape_string'], array_values($row))) . "'";
                        $conn->query("INSERT INTO $table ($columns) VALUES ($values)");
                    }
                }
            }
            
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Database restored successfully']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
?>