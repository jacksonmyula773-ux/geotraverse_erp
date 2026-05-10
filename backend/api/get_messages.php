<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

require_once '../config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$current_dept = isset($_SESSION['department_id']) ? $_SESSION['department_id'] : 1;

$sql = "SELECT DISTINCT 
            CASE 
                WHEN c.participant_1 = ? THEN c.participant_2
                ELSE c.participant_1
            END as other_dept_id,
            d.name as other_dept_name,
            c.last_message,
            c.last_message_time,
            (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND receiver_dept = ? AND is_read = 0) as unread_count
        FROM conversations c
        JOIN departments d ON (CASE WHEN c.participant_1 = ? THEN c.participant_2 ELSE c.participant_1 END) = d.id
        WHERE c.participant_1 = ? OR c.participant_2 = ?
        ORDER BY c.last_message_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiii", $current_dept, $current_dept, $current_dept, $current_dept, $current_dept);
$stmt->execute();
$result = $stmt->get_result();
$conversations = [];

while ($row = $result->fetch_assoc()) {
    $conversations[] = $row;
}

$unread_count = 0;
foreach ($conversations as $conv) {
    $unread_count += $conv['unread_count'];
}

echo json_encode([
    'success' => true,
    'data' => $conversations,
    'unread_count' => $unread_count
]);
?>