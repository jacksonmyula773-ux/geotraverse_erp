<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$host = "localhost";
$db_name = "geotraverse_erp";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=" . $host . ";dbname=" . $db_name . ";charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database connection failed", "data" => []]);
    exit();
}

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id === 0) {
    echo json_encode(["success" => false, "message" => "Missing user_id", "data" => []]);
    exit();
}

$query = "
    SELECT 
        c.id,
        c.subject,
        c.created_at,
        c.updated_at,
        c.user_id,
        c.admin_id,
        u1.name as user_name,
        u1.department_id as user_department_id,
        d1.name as user_department_name,
        u2.name as admin_name,
        u2.department_id as admin_department_id,
        d2.name as admin_department_name,
        (SELECT message FROM messages 
         WHERE conversation_id = c.id 
         AND ((sender_id = ? AND sender_deleted = 0) OR (receiver_id = ? AND receiver_deleted = 0))
         ORDER BY created_at DESC LIMIT 1) as last_message,
        (SELECT created_at FROM messages 
         WHERE conversation_id = c.id 
         AND ((sender_id = ? AND sender_deleted = 0) OR (receiver_id = ? AND receiver_deleted = 0))
         ORDER BY created_at DESC LIMIT 1) as last_message_time,
        (SELECT COUNT(*) FROM messages 
         WHERE conversation_id = c.id 
         AND receiver_id = ? 
         AND is_read = 0 
         AND receiver_deleted = 0) as unread_count
    FROM conversations c
    LEFT JOIN users u1 ON c.user_id = u1.id
    LEFT JOIN departments d1 ON u1.department_id = d1.id
    LEFT JOIN users u2 ON c.admin_id = u2.id
    LEFT JOIN departments d2 ON u2.department_id = d2.id
    WHERE (c.user_id = ? OR c.admin_id = ?)
    AND c.status = 'active'
    ORDER BY c.updated_at DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(["success" => true, "data" => $conversations]);
?>