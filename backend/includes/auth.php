<?php
class Auth {
    public function validateToken() {
        // For now, using session-based authentication
        session_start();
        
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['department_id'])) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'department_id' => $_SESSION['department_id'],
            'email' => $_SESSION['email'] ?? null,
            'role' => $_SESSION['role'] ?? null
        ];
    }
    
    public function login($userId, $departmentId, $email, $role = null) {
        session_start();
        $_SESSION['user_id'] = $userId;
        $_SESSION['department_id'] = $departmentId;
        $_SESSION['email'] = $email;
        $_SESSION['role'] = $role;
    }
    
    public function logout() {
        session_start();
        session_destroy();
    }
}

function sendResponse($success, $data = null, $error = null, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        "success" => $success,
        "data" => $data,
        "error" => $error
    ]);
    exit();
}
?>