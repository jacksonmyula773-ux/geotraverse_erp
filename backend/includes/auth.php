<?php
session_start();

require_once __DIR__ . '/../config/database.php';

// Department IDs mapping
define('DEPT_SUPER_ADMIN', 1);
define('DEPT_FINANCE', 2);
define('DEPT_SALES_MARKETING', 3);
define('DEPT_MANAGER', 4);
define('DEPT_SECRETARY', 5);
define('DEPT_BRICKS_TIMBER', 6);
define('DEPT_ALUMINIUM', 7);
define('DEPT_TOWN_PLANNING', 8);
define('DEPT_ARCHITECTURAL', 9);
define('DEPT_SURVEY', 10);
define('DEPT_CONSTRUCTION', 11);
define('DEPT_HATIMILIKI', 12);

// Department permissions mapping based on department_id
$department_permissions = [
    DEPT_SUPER_ADMIN => [
        'view_all', 'add', 'edit', 'delete', 'manage_users', 'backup', 'restore',
        'view_finance', 'view_sales', 'view_manager', 'view_secretary',
        'view_bricks', 'view_aluminium', 'view_town_planning', 'view_architectural',
        'view_survey', 'view_construction', 'view_hatimiliki'
    ],
    
    DEPT_FINANCE => [
        'view_transactions', 'add_transaction', 'edit_transaction', 'delete_transaction',
        'view_budgets', 'edit_budgets', 'view_reports', 'add_report', 'send_report',
        'record_payment', 'view_department_data'
    ],
    
    DEPT_SALES_MARKETING => [
        'view_projects', 'add_project', 'edit_project', 'view_leads', 'add_lead', 'edit_lead',
        'view_reports', 'add_report', 'view_department_data'
    ],
    
    DEPT_MANAGER => [
        'view_all_projects', 'edit_project', 'view_daily_work', 'add_daily_work', 'edit_daily_work',
        'view_employees', 'manage_employees', 'view_reports', 'add_report', 'view_department_data'
    ],
    
    DEPT_SECRETARY => [
        'view_projects', 'view_messages', 'send_message', 'delete_message',
        'view_reports', 'add_report', 'visitor_management', 'view_department_data'
    ],
    
    DEPT_BRICKS_TIMBER => [
        'view_production', 'add_production', 'edit_production', 'view_projects',
        'view_daily_work', 'add_daily_work', 'view_department_data'
    ],
    
    DEPT_ALUMINIUM => [
        'view_production', 'add_production', 'edit_production', 'view_projects',
        'view_daily_work', 'add_daily_work', 'view_department_data'
    ],
    
    DEPT_TOWN_PLANNING => [
        'view_projects', 'add_project', 'edit_project', 'view_daily_work',
        'add_daily_work', 'edit_daily_work', 'view_department_data'
    ],
    
    DEPT_ARCHITECTURAL => [
        'view_projects', 'add_project', 'edit_project', 'view_daily_work',
        'add_daily_work', 'edit_daily_work', 'view_department_data'
    ],
    
    DEPT_SURVEY => [
        'view_projects', 'add_project', 'edit_project', 'view_daily_work',
        'add_daily_work', 'edit_daily_work', 'view_department_data'
    ],
    
    DEPT_CONSTRUCTION => [
        'view_projects', 'add_project', 'edit_project', 'view_daily_work',
        'add_daily_work', 'edit_daily_work', 'view_department_data'
    ],
    
    DEPT_HATIMILIKI => [
        'view_projects', 'edit_project', 'view_reports', 'add_report',
        'view_daily_work', 'add_daily_work', 'view_department_data'
    ]
];

/**
 * Authenticate user from session
 */
function authenticate() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['department_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login.']);
        exit();
    }
    return true;
}

/**
 * Check if current user has specific permission
 */
function has_permission($permission) {
    global $department_permissions;
    
    if (!isset($_SESSION['department_id'])) {
        return false;
    }
    
    $dept_id = $_SESSION['department_id'];
    
    // Super admin can do everything
    if ($dept_id == DEPT_SUPER_ADMIN) {
        return true;
    }
    
    if (!isset($department_permissions[$dept_id])) {
        return false;
    }
    
    return in_array($permission, $department_permissions[$dept_id]);
}

/**
 * Require specific permission or exit
 */
function require_permission($permission) {
    authenticate();
    
    if (!has_permission($permission)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied. You don\'t have permission for this action.']);
        exit();
    }
    return true;
}

/**
 * Get current user's department ID
 */
function get_current_department_id() {
    return $_SESSION['department_id'] ?? null;
}

/**
 * Get current user's role
 */
function get_current_role() {
    return $_SESSION['role'] ?? null;
}

/**
 * Get current user ID
 */
function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Verify user login credentials
 */
function verify_user($email, $password) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT u.id, u.name, u.email, u.password, u.role, u.department_id, u.status,
               d.name as department_name, d.icon, d.color
        FROM users u
        LEFT JOIN departments d ON u.department_id = d.id
        WHERE u.email = ? AND u.status = 'Active'
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['department_id'] = $user['department_id'];
            $_SESSION['department_name'] = $user['department_name'];
            
            // Log activity
            log_activity($user['id'], $user['department_id'], 'Logged in');
            
            return [
                'success' => true,
                'user_id' => $user['id'],
                'name' => $user['name'],
                'role' => $user['role'],
                'department_id' => $user['department_id'],
                'department_name' => $user['department_name']
            ];
        }
    }
    
    return ['success' => false, 'message' => 'Invalid email or password.'];
}

/**
 * Log user activity
 */
function log_activity($user_id, $department_id, $action) {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, department_id, action, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $user_id, $department_id, $action, $ip);
    $stmt->execute();
}

/**
 * Logout user
 */
function logout_user() {
    if (isset($_SESSION['user_id'])) {
        log_activity($_SESSION['user_id'], $_SESSION['department_id'], 'Logged out');
    }
    session_destroy();
    return ['success' => true, 'message' => 'Logged out successfully.'];
}

/**
 * Get dashboard redirect URL based on department
 */
function get_dashboard_redirect() {
    $dept_id = get_current_department_id();
    
    $redirect_map = [
        DEPT_SUPER_ADMIN => '../frontend/super_admin.html',
        DEPT_FINANCE => '../frontend/finance.html',
        DEPT_SALES_MARKETING => '../frontend/sales_marketing.html',
        DEPT_MANAGER => '../frontend/manager.html',
        DEPT_SECRETARY => '../frontend/secretary.html',
        DEPT_BRICKS_TIMBER => '../frontend/bricks_timber.html',
        DEPT_ALUMINIUM => '../frontend/aluminium.html',
        DEPT_TOWN_PLANNING => '../frontend/town_planning.html',
        DEPT_ARCHITECTURAL => '../frontend/architectural.html',
        DEPT_SURVEY => '../frontend/survey.html',
        DEPT_CONSTRUCTION => '../frontend/construction.html',
        DEPT_HATIMILIKI => '../frontend/hatimiliki.html'
    ];
    
    return $redirect_map[$dept_id] ?? '../frontend/login_system.html';
}

/**
 * Get data filtered by department (for non-admin users)
 */
function get_department_filtered_query($table, $department_column = 'department_id') {
    $dept_id = get_current_department_id();
    
    if ($dept_id == DEPT_SUPER_ADMIN) {
        return "SELECT * FROM $table";
    } else {
        return "SELECT * FROM $table WHERE $department_column = $dept_id";
    }
}
?>