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

$conversation_id = isset($_GET['conversation_id']) ? intval($_GET['conversation_id']) : 0;
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($conversation_id === 0 || $user_id === 0) {
    echo json_encode(["success" => false, "message" => "Missing parameters", "data" => []]);
    exit();
}

// Mark messages as read for this user
$markRead = $pdo->prepare("UPDATE messages SET is_read = 1, read_at = NOW() 
                          WHERE conversation_id = ? AND receiver_id = ? AND is_read = 0");
$markRead->execute([$conversation_id, $user_id]);

// Get messages that are NOT deleted by this user
$query = "
    SELECT 
        m.id,
        m.conversation_id,
        m.sender_id,
        m.receiver_id,
        m.message,
        m.is_read,
        m.created_at,
        u_sender.name as sender_name,
        d_sender.name as sender_department,
        CASE 
            WHEN m.sender_id = ? THEN 'sent'
            ELSE 'received'
        END as message_type
    FROM messages m
    JOIN users u_sender ON m.sender_id = u_sender.id
    LEFT JOIN departments d_sender ON u_sender.department_id = d_sender.id
    WHERE m.conversation_id = ?
    AND ((m.sender_id = ? AND m.sender_deleted = 0) OR (m.receiver_id = ? AND m.receiver_deleted = 0))
    ORDER BY m.created_at ASC
";

$stmt = $pdo->prepare($query);
$stmt->execute([$user_id, $conversation_id, $user_id, $user_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(["success" => true, "data" => $messages]);
?>