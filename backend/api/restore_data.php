<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid backup data']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();
    
    if (isset($data['employees'])) {
        $db->exec("DELETE FROM users WHERE id > 0");
        foreach ($data['employees'] as $emp) {
            $stmt = $db->prepare("INSERT INTO users (id, name, email, phone, department_id, role, salary, password, status, join_date, created_at) 
                                  VALUES (:id, :name, :email, :phone, :dept_id, :role, :salary, :password, :status, :join_date, :created_at)");
            $stmt->execute($emp);
        }
    }
    
    if (isset($data['projects'])) {
        $db->exec("DELETE FROM projects WHERE id > 0");
        foreach ($data['projects'] as $proj) {
            $stmt = $db->prepare("INSERT INTO projects (id, name, client_name, amount, status, progress, location, description, image, department_id, created_at, updated_at) 
                                  VALUES (:id, :name, :client_name, :amount, :status, :progress, :location, :description, :image, :dept_id, :created_at, :updated_at)");
            $stmt->execute($proj);
        }
    }
    
    if (isset($data['transactions'])) {
        $db->exec("DELETE FROM transactions WHERE id > 0");
        foreach ($data['transactions'] as $trans) {
            $stmt = $db->prepare("INSERT INTO transactions (id, type, department_id, source, amount, paid_amount, status, description, transaction_date) 
                                  VALUES (:id, :type, :dept_id, :source, :amount, :paid_amount, :status, :description, :transaction_date)");
            $stmt->execute($trans);
        }
    }
    
    if (isset($data['reports'])) {
        $db->exec("DELETE FROM reports WHERE id > 0");
        foreach ($data['reports'] as $report) {
            $stmt = $db->prepare("INSERT INTO reports (id, department_id, title, period, content, status, created_at) 
                                  VALUES (:id, :dept_id, :title, :period, :content, :status, :created_at)");
            $stmt->execute($report);
        }
    }
    
    if (isset($data['messages'])) {
        $db->exec("DELETE FROM messages WHERE id > 0");
        foreach ($data['messages'] as $msg) {
            $stmt = $db->prepare("INSERT INTO messages (id, from_department_id, to_department_id, message, is_read, created_at) 
                                  VALUES (:id, :from_dept, :to_dept, :message, :is_read, :created_at)");
            $stmt->execute($msg);
        }
    }
    
    $db->commit();
    echo json_encode(['success' => true]);
} catch(Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>