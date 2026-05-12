<?php
// backend/api/get_budgets.php
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

$query = "SELECT b.*, d.name as department_name 
          FROM budget_allocations b
          LEFT JOIN departments d ON b.department_id = d.id
          ORDER BY b.department_id, b.year DESC, b.month DESC";

$stmt = $db->prepare($query);
$stmt->execute();

$budgets = array();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $budgets[] = array(
        'id' => (int)$row['id'],
        'category' => $row['category'],
        'allocated_amount' => floatval($row['allocated_amount']),
        'used_amount' => floatval($row['used_amount']),
        'year' => (int)$row['year'],
        'month' => (int)$row['month'],
        'department_id' => (int)$row['department_id'],
        'department_name' => $row['department_name'],
        'description' => $row['description']
    );
}

echo json_encode(["success" => true, "data" => $budgets]);
exit();
?>