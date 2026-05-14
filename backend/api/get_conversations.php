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

// Get user's department
$userQuery = $pdo->prepare("SELECT department_id FROM users WHERE id = ?");
$userQuery->execute([$user_id]);
$user = $userQuery->fetch(PDO::FETCH_ASSOC);
$user_dept_id = $user ? $user['department_id'] : 1;

// Ensure columns exist
$pdo->exec("ALTER TABLE conversations ADD COLUMN IF NOT EXISTS deleted_by_admin TINYINT DEFAULT 0");
$pdo->exec("ALTER TABLE conversations ADD COLUMN IF NOT EXISTS deleted_by_department TINYINT DEFAULT 0");
$pdo->exec("ALTER TABLE conversations ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL");

// Build condition for filtering deleted conversations
if ($user_dept_id == 1) {
    // Admin: exclude conversations where deleted_by_admin = 1
    $deletedCondition = "(c.deleted_by_admin = 0 OR c.deleted_by_admin IS NULL)";
} else {
    // Department: exclude conversations where deleted_by_department = 1
    $deletedCondition = "(c.deleted_by_department = 0 OR c.deleted_by_department IS NULL)";
}

// Get all conversations where user is involved AND not deleted by this user
$query = "
    SELECT 
        c.id as conversation_id,
        c.subject,
        c.created_at,
        c.updated_at,
        c.user_id,
        c.admin_id,
        c.deleted_by_admin,
        c.deleted_by_department,
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
    AND $deletedCondition
    ORDER BY c.updated_at DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$result = [];

foreach ($conversations as $conv) {
    // Check if there are any messages not deleted by this user
    $checkMsg = $pdo->prepare("
        SELECT COUNT(*) as cnt FROM messages 
        WHERE conversation_id = ? 
        AND ((sender_id = ? AND sender_deleted = 0) OR (receiver_id = ? AND receiver_deleted = 0))
    ");
    $checkMsg->execute([$conv['conversation_id'], $user_id, $user_id]);
    $msgCount = $checkMsg->fetch(PDO::FETCH_ASSOC);
    
    // Only include if there are messages (conversation not empty for this user)
    if ($msgCount['cnt'] > 0) {
        $otherUserId = ($conv['user_id'] == $user_id) ? $conv['admin_id'] : $conv['user_id'];
        $otherDeptQuery = $pdo->prepare("SELECT u.department_id, d.name as department_name 
                                         FROM users u 
                                         LEFT JOIN departments d ON u.department_id = d.id 
                                         WHERE u.id = ?");
        $otherDeptQuery->execute([$otherUserId]);
        $otherDept = $otherDeptQuery->fetch(PDO::FETCH_ASSOC);
        
        $result[] = [
            'conversation_id' => $conv['conversation_id'],
            'subject' => $conv['subject'],
            'last_message' => $conv['last_message'],
            'last_message_time' => $conv['last_message_time'],
            'unread_count' => (int)$conv['unread_count'],
            'other_department_id' => $otherDept ? (int)$otherDept['department_id'] : 1,
            'other_department_name' => $otherDept ? $otherDept['department_name'] : 'Super Admin'
        ];
    }
}

echo json_encode(["success" => true, "data" => $result]);
?>