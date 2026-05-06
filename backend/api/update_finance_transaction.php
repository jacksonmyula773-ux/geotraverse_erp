<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$id = $_GET['id'] ?? 0;
$data = json_decode(file_get_contents('php://input'), true);

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Transaction ID required']);
    exit();
}

$host = 'localhost';
$dbname = 'geotraverse_erp';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("UPDATE transactions SET type = :type, source = :source, amount = :amount, 
                           transaction_date = :date, status = :status, description = :desc WHERE id = :id");
    $stmt->bindParam(':type', $data['type']);
    $stmt->bindParam(':source', $data['source']);
    $stmt->bindParam(':amount', $data['amount']);
    $stmt->bindParam(':date', $data['transaction_date']);
    $stmt->bindParam(':status', $data['status']);
    $stmt->bindParam(':desc', $data['description']);
    $stmt->bindParam(':id', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update']);
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>