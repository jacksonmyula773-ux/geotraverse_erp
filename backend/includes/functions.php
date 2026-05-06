<?php
/**
 * GeoTraverse ERP - Common Functions
 */

/**
 * Format currency to TZS
 */
function formatCurrency($amount) {
    return "TZS " . number_format((float)$amount, 2);
}

/**
 * Format date to readable format
 */
function formatDate($date, $format = 'Y-m-d') {
    if (!$date) return 'N/A';
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

/**
 * Sanitize input
 */
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Upload file
 */
function uploadFile($file, $targetDir, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf']) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload error'];
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedTypes)) {
        return ['success' => false, 'error' => 'File type not allowed'];
    }
    
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    $filepath = $targetDir . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'filepath' => $filepath];
    }
    
    return ['success' => false, 'error' => 'Failed to move uploaded file'];
}

/**
 * Delete file
 */
function deleteFile($filepath) {
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * Get file size human readable
 */
function humanFileSize($bytes, $decimals = 2) {
    $size = ['B', 'KB', 'MB', 'GB', 'TB'];
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . $size[$factor];
}

/**
 * Create backup of database tables
 */
function createDatabaseBackup($db, $tables = '*') {
    $return = '';
    
    // Get all tables
    if ($tables == '*') {
        $tables = [];
        $result = $db->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
    } else {
        $tables = is_array($tables) ? $tables : explode(',', $tables);
    }
    
    foreach ($tables as $table) {
        $result = $db->query("SELECT * FROM $table");
        $numFields = $result->columnCount();
        
        $return .= "DROP TABLE IF EXISTS $table;\n";
        
        $row2 = $db->query("SHOW CREATE TABLE $table")->fetch(PDO::FETCH_NUM);
        $return .= $row2[1] . ";\n\n";
        
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $return .= "INSERT INTO $table VALUES(";
            for ($j = 0; $j < $numFields; $j++) {
                $row[$j] = addslashes($row[$j]);
                $row[$j] = str_replace("\n", "\\n", $row[$j]);
                if (isset($row[$j])) {
                    $return .= '"' . $row[$j] . '"';
                } else {
                    $return .= 'NULL';
                }
                if ($j < ($numFields - 1)) $return .= ',';
            }
            $return .= ");\n";
        }
        $return .= "\n\n";
    }
    
    return $return;
}

/**
 * Get dashboard statistics
 */
function getDashboardStats($db, $department_id = null) {
    $stats = [];
    
    // Total employees
    $query = "SELECT COUNT(*) as total FROM users";
    if ($department_id) $query .= " WHERE department_id = :dept_id";
    $stmt = $db->prepare($query);
    if ($department_id) $stmt->bindParam(':dept_id', $department_id);
    $stmt->execute();
    $stats['employees'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total projects
    $query = "SELECT COUNT(*) as total FROM projects";
    if ($department_id) $query .= " WHERE department_id = :dept_id OR department_id IS NULL";
    $stmt = $db->prepare($query);
    if ($department_id) $stmt->bindParam(':dept_id', $department_id);
    $stmt->execute();
    $stats['projects'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Financial stats
    $query = "SELECT 
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expenses
              FROM transactions";
    if ($department_id) $query .= " WHERE department_id = :dept_id";
    $stmt = $db->prepare($query);
    if ($department_id) $stmt->bindParam(':dept_id', $department_id);
    $stmt->execute();
    $finance = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_income'] = $finance['total_income'] ?? 0;
    $stats['total_expenses'] = $finance['total_expenses'] ?? 0;
    $stats['net_profit'] = $stats['total_income'] - $stats['total_expenses'];
    
    // Unread messages
    $query = "SELECT COUNT(*) as total FROM messages WHERE to_department_id = :dept_id AND is_read = 0";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':dept_id', $department_id ?? 1);
    $stmt->execute();
    $stats['unread_messages'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    return $stats;
}
?>