<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$host = 'localhost';
$dbname = 'geotraverse_erp';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$settings) {
        $settings = [
            'company_name' => 'GeoTraverse Group',
            'company_address' => 'Dar es Salaam, Tanzania',
            'company_email' => 'info@geotraverse.com',
            'company_phone' => '+255 719 336 667',
            'company_logo' => ''
        ];
    }
    
    echo json_encode(['success' => true, 'settings' => $settings]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>