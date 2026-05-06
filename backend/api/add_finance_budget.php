<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$data = json_decode(file_get_contents('php://input'), true);

$category = $data['category'] ?? '';
$allocated_amount = $data['allocated_amount'] ?? 0;
$used_amount = $data['used_amount'] ?? 0;
$year = $data['year'] ?? date('Y');
$month = $data['month'] ?? date('m');
$department_id = 2;

if (empty($category) || $allocated_amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Category and amount required']);
    exit();
}

$host = 'localhost';
$dbname = 'geotraverse_erp';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("INSERT INTO budgets (department_id, category, allocated_amount, used_amount, year, month) 
                           VALUES (:dept_id, :category, :allocated, :used, :year, :month)");
    $stmt->bindParam(':dept_id', $department_id);
    $stmt->bindParam(':category', $category);
    $stmt->bindParam(':allocated', $allocated_amount);
    $stmt->bindParam(':used', $used_amount);
    $stmt->bindParam(':year', $year);
    $stmt->bindParam(':month', $month);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'budget_id' => $pdo->lastInsertId()]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to add budget']);
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>