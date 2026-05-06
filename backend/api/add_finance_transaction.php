<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$data = json_decode(file_get_contents('php://input'), true);

$type = $data['type'] ?? 'income';
$source = $data['source'] ?? '';
$amount = $data['amount'] ?? 0;
$transaction_date = $data['transaction_date'] ?? date('Y-m-d');
$status = $data['status'] ?? 'paid';
$description = $data['description'] ?? '';
$department_id = 2; // Finance department

if (empty($source) || $amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Source and amount required']);
    exit();
}

$host = 'localhost';
$dbname = 'geotraverse_erp';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("INSERT INTO transactions (type, department_id, source, amount, transaction_date, status, description) 
                           VALUES (:type, :dept_id, :source, :amount, :date, :status, :desc)");
    $stmt->bindParam(':type', $type);
    $stmt->bindParam(':dept_id', $department_id);
    $stmt->bindParam(':source', $source);
    $stmt->bindParam(':amount', $amount);
    $stmt->bindParam(':date', $transaction_date);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':desc', $description);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'transaction_id' => $pdo->lastInsertId()]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to add transaction']);
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>