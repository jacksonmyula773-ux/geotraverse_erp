<?php
// backend/api/get_dashboard_stats.php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get total employees
$query = "SELECT COUNT(*) as total FROM users WHERE is_active = 1";
$stmt = $db->prepare($query);
$stmt->execute();
$employees = $stmt->fetch(PDO::FETCH_ASSOC);

// Get project stats
$query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM projects";
$stmt = $db->prepare($query);
$stmt->execute();
$projects = $stmt->fetch(PDO::FETCH_ASSOC);

// Get financial stats
$query = "SELECT 
    SUM(CASE WHEN type = 'income' AND status = 'paid' THEN amount ELSE 0 END) as total_income,
    SUM(CASE WHEN type = 'expense' AND status = 'paid' THEN amount ELSE 0 END) as total_expense
    FROM transactions";
$stmt = $db->prepare($query);
$stmt->execute();
$finance = $stmt->fetch(PDO::FETCH_ASSOC);

// Get unread messages count for admin
$query = "SELECT COUNT(*) as unread FROM messages WHERE receiver_dept = 1 AND is_read = 0";
$stmt = $db->prepare($query);
$stmt->execute();
$messages = $stmt->fetch(PDO::FETCH_ASSOC);

sendResponse(true, [
    'total_employees' => $employees['total'],
    'total_projects' => $projects['total'],
    'pending_projects' => $projects['pending'],
    'in_progress_projects' => $projects['in_progress'],
    'completed_projects' => $projects['completed'],
    'total_income' => floatval($finance['total_income']),
    'total_expense' => floatval($finance['total_expense']),
    'unread_messages' => $messages['unread']
]);
?>