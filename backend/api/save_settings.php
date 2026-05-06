<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$data = json_decode(file_get_contents('php://input'), true);

$company_name = $data['company_name'] ?? '';
$company_address = $data['company_address'] ?? '';
$company_email = $data['company_email'] ?? '';
$company_phone = $data['company_phone'] ?? '';

$host = 'localhost';
$dbname = 'geotraverse_erp';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("INSERT INTO settings (id, company_name, company_address, company_email, company_phone) 
                           VALUES (1, :name, :address, :email, :phone) 
                           ON DUPLICATE KEY UPDATE 
                           company_name = :name, company_address = :address, company_email = :email, company_phone = :phone");
    $stmt->bindParam(':name', $company_name);
    $stmt->bindParam(':address', $company_address);
    $stmt->bindParam(':email', $company_email);
    $stmt->bindParam(':phone', $company_phone);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>