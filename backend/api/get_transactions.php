<?php
// backend/api/get_transactions.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$host = "localhost";
$db_name = "geotraverse_erp";
$username = "root";
$password = "";

try {
    $db = new PDO("mysql:host=" . $host . ";dbname=" . $db_name . ";charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database connection failed: " . $e->getMessage()]);
    exit();
}

$department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;

if ($department_id > 0) {
    $query = "SELECT t.*, d.name as department_name 
              FROM transactions t
              LEFT JOIN departments d ON t.department_id = d.id
              WHERE t.department_id = :dept_id
              ORDER BY t.transaction_date DESC, t.id DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':dept_id', $department_id);
} else {
    $query = "SELECT t.*, d.name as department_name 
              FROM transactions t
              LEFT JOIN departments d ON t.department_id = d.id
              ORDER BY t.transaction_date DESC, t.id DESC";
    $stmt = $db->prepare($query);
}

$stmt->execute();

$transactions = array();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $transactions[] = array(
        'id' => (int)$row['id'],
        'type' => $row['type'],
        'source' => $row['source'],
        'amount' => floatval($row['amount']),
        'paid_amount' => floatval($row['paid_amount']),
        'transaction_date' => $row['transaction_date'],
        'status' => $row['status'],
        'description' => $row['description'],
        'department_id' => (int)$row['department_id'],
        'department_name' => $row['department_name']
    );
}

echo json_encode(["success" => true, "data" => $transactions]);
exit();
?>