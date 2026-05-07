<?php
// backend/api/check_session.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

session_start();

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    // Determine redirect page
    $redirectPage = "super_admin.html";
    $departmentPages = [
        2 => "finance.html",
        3 => "sales_marketing.html",
        4 => "manager.html",
        5 => "secretary.html",
        6 => "bricks_timber.html",
        7 => "aluminium.html",
        8 => "town_planning.html",
        9 => "architectural.html",
        10 => "survey.html",
        11 => "construction.html",
        12 => "hatimiliki.html"
    ];
    
    if ($_SESSION['department_id'] != 1 && isset($departmentPages[$_SESSION['department_id']])) {
        $redirectPage = $departmentPages[$_SESSION['department_id']];
    }
    
    echo json_encode([
        "success" => true,
        "logged_in" => true,
        "user" => [
            "id" => $_SESSION['user_id'],
            "name" => $_SESSION['user_name'],
            "email" => $_SESSION['user_email'],
            "department_id" => $_SESSION['department_id'],
            "department_name" => $_SESSION['department_name'],
            "role" => $_SESSION['role']
        ],
        "redirect" => $redirectPage
    ]);
} else {
    echo json_encode([
        "success" => true,
        "logged_in" => false
    ]);
}
?><?php
// backend/api/check_session.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    echo json_encode([
        'success' => true,
        'logged_in' => true,
        'user' => [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'department_id' => $_SESSION['department_id'],
            'department_name' => $_SESSION['department_name'],
            'role' => $_SESSION['role']
        ]
    ]);
} else {
    echo json_encode([
        'success' => true,
        'logged_in' => false
    ]);
}
?>