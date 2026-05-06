<?php
/**
 * GeoTraverse ERP - Authentication Helper
 */

session_start();

class Auth {
    
    /**
     * Validate user session/token
     */
    public function validateToken() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['department_id'])) {
            // Check for Bearer token in Authorization header
            $headers = getallheaders();
            if (isset($headers['Authorization'])) {
                $token = str_replace('Bearer ', '', $headers['Authorization']);
                return $this->validateJWT($token);
            }
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'department_id' => $_SESSION['department_id'],
            'email' => $_SESSION['email'] ?? null,
            'role' => $_SESSION['role'] ?? null,
            'name' => $_SESSION['name'] ?? null
        ];
    }
    
    /**
     * Validate JWT token (for API calls)
     */
    private function validateJWT($token) {
        // Simple validation for now
        // In production, use Firebase/JWT library
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;
        
        $payload = json_decode(base64_decode($parts[1]), true);
        if (!$payload || (isset($payload['exp']) && $payload['exp'] < time())) {
            return null;
        }
        
        return [
            'id' => $payload['user_id'] ?? null,
            'department_id' => $payload['department_id'] ?? null,
            'email' => $payload['email'] ?? null,
            'role' => $payload['role'] ?? null
        ];
    }
    
    /**
     * Create JWT token
     */
    public function createJWT($userData) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'user_id' => $userData['id'],
            'department_id' => $userData['department_id'],
            'email' => $userData['email'],
            'role' => $userData['role'] ?? null,
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60) // 24 hours
        ]);
        
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, 'geotraverse_secret_key_2024', true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }
    
    /**
     * Login user
     */
    public function login($userId, $departmentId, $email, $role = null, $name = null) {
        $_SESSION['user_id'] = $userId;
        $_SESSION['department_id'] = $departmentId;
        $_SESSION['email'] = $email;
        $_SESSION['role'] = $role;
        $_SESSION['name'] = $name;
        $_SESSION['logged_in'] = true;
        session_regenerate_id(true);
    }
    
    /**
     * Logout user
     */
    public function logout() {
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * Check if user is Super Admin
     */
    public function isSuperAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'Super Admin';
    }
    
    /**
     * Get current user info
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) return null;
        return [
            'id' => $_SESSION['user_id'],
            'department_id' => $_SESSION['department_id'],
            'email' => $_SESSION['email'],
            'role' => $_SESSION['role'],
            'name' => $_SESSION['name']
        ];
    }
}

/**
 * Global helper function for API responses
 */
function sendResponse($success, $data = null, $error = null, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode([
        "success" => $success,
        "data" => $data,
        "error" => $error
    ]);
    exit();
}

/**
 * Global helper for logging activities
 */
function logActivity($userId, $action, $details = null) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO activity_logs (user_id, action, details, ip_address, created_at) 
              VALUES (:user_id, :action, :details, :ip, NOW())";
    $stmt = $db->prepare($query);
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':action', $action);
    $stmt->bindParam(':details', $details);
    $stmt->bindParam(':ip', $ip);
    $stmt->execute();
}
?>