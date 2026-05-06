<?php
/**
 * Get All Conversations for a Department
 * Method: GET
 * Returns list of departments that have exchanged messages with current department
 */

require_once '../config/database.php';
require_once '../includes/auth.php';

$auth = new Auth();
$user = $auth->validateToken();

if (!$user) {
    sendResponse(false, null, "Unauthorized", 401);
}

$database = new Database();
$db = $database->getConnection();

$my_dept_id = $user['department_id'];

// Get all unique departments that have exchanged messages with current department
$query = "SELECT DISTINCT 
            CASE 
                WHEN from_department_id = :my_dept THEN to_department_id
                ELSE from_department_id
            END as other_dept_id,
            d.name as department_name,
            d.email as department_email,
            (SELECT message FROM messages m2 
             WHERE (m2.from_department_id = :my_dept AND m2.to_department_id = other_dept_id)
                OR (m2.from_department_id = other_dept_id AND m2.to_department_id = :my_dept)
             ORDER BY m2.created_at DESC LIMIT 1) as last_message,
            (SELECT created_at FROM messages m3 
             WHERE (m3.from_department_id = :my_dept AND m3.to_department_id = other_dept_id)
                OR (m3.from_department_id = other_dept_id AND m3.to_department_id = :my_dept)
             ORDER BY m3.created_at DESC LIMIT 1) as last_message_time,
            (SELECT COUNT(*) FROM messages m4 
             WHERE m4.to_department_id = :my_dept 
             AND m4.from_department_id = other_dept_id 
             AND m4.is_read = 0) as unread_count
          FROM messages m
          LEFT JOIN departments d ON d.id = (CASE 
              WHEN from_department_id = :my_dept THEN to_department_id
              ELSE from_department_id
          END)
          WHERE from_department_id = :my_dept OR to_department_id = :my_dept
          ORDER BY last_message_time DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':my_dept', $my_dept_id);
$stmt->execute();

$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

sendResponse(true, $conversations);
?>