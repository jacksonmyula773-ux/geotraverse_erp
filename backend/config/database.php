<?php
// backend/config/database.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

class Database {
    private $host = "localhost";
    private $db_name = "geotraverse_erp";
    private $username = "root";
    private $password = "";
    public $conn;

    /**
     * Get database connection
     * @return PDO|null
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                )
            );
            $this->conn->exec("set names utf8mb4");
        } catch(PDOException $exception) {
            $error = array(
                "success" => false, 
                "message" => "Database connection error: " . $exception->getMessage(),
                "error_code" => $exception->getCode()
            );
            echo json_encode($error);
            exit();
        }
        
        return $this->conn;
    }
    
    /**
     * Test database connection
     * @return bool
     */
    public function testConnection() {
        try {
            $conn = $this->getConnection();
            if ($conn) {
                $stmt = $conn->query("SELECT 1");
                return true;
            }
        } catch(Exception $e) {
            return false;
        }
        return false;
    }
    
    /**
     * Get table row count
     * @param string $table
     * @return int
     */
    public function getTableCount($table) {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->query("SELECT COUNT(*) as count FROM `$table`");
            $result = $stmt->fetch();
            return (int)$result['count'];
        } catch(Exception $e) {
            return 0;
        }
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        if ($this->conn) {
            $this->conn->beginTransaction();
        }
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        if ($this->conn) {
            $this->conn->commit();
        }
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        if ($this->conn) {
            $this->conn->rollBack();
        }
    }
    
    /**
     * Get last insert ID
     * @return string
     */
    public function lastInsertId() {
        if ($this->conn) {
            return $this->conn->lastInsertId();
        }
        return 0;
    }
    
    /**
     * Escape string for safe SQL (fallback when prepared statements can't be used)
     * @param string $value
     * @return string
     */
    public function escapeString($value) {
        if ($this->conn) {
            return $this->conn->quote($value);
        }
        return addslashes($value);
    }
}

/**
 * Send JSON response
 * @param bool $success
 * @param mixed $data
 * @param string $message
 * @param int|null $unviewed_count
 */
function sendResponse($success, $data = null, $message = "", $unviewed_count = null) {
    $response = array("success" => $success);
    
    if ($data !== null) {
        $response["data"] = $data;
    }
    
    if ($message !== "") {
        $response["message"] = $message;
    }
    
    if ($unviewed_count !== null) {
        $response["unviewed_count"] = $unviewed_count;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

/**
 * Send error response
 * @param string $message
 * @param int $code
 */
function sendError($message, $code = 400) {
    http_response_code($code);
    sendResponse(false, null, $message);
}

/**
 * Validate required fields
 * @param array $data
 * @param array $required
 * @return bool|string
 */
function validateRequired($data, $required) {
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            return "Field '$field' is required";
        }
    }
    return true;
}

/**
 * Sanitize input data
 * @param string $input
 * @return string
 */
function sanitizeInput($input) {
    if ($input === null) return null;
    $input = trim($input);
    $input = strip_tags($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

/**
 * Format amount to float
 * @param mixed $amount
 * @return float
 */
function formatAmount($amount) {
    if ($amount === null || $amount === '') return 0.00;
    
    // Remove commas and any non-numeric characters except dot
    $amount = preg_replace('/[^0-9.-]/', '', (string)$amount);
    return floatval($amount);
}

/**
 * Format amount for display
 * @param float $amount
 * @return string
 */
function displayAmount($amount) {
    return number_format(floatval($amount), 2, '.', ',');
}

/**
 * Get current datetime for MySQL
 * @return string
 */
function getCurrentDateTime() {
    return date('Y-m-d H:i:s');
}

/**
 * Get current date for MySQL
 * @return string
 */
function getCurrentDate() {
    return date('Y-m-d');
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

/**
 * Get current user ID
 * @return int|null
 */
function getCurrentUserId() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION['user_id'] ?? 1; // Default to admin if not set
}

/**
 * Get current department ID (admin = 1)
 * @return int
 */
function getCurrentDepartmentId() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION['department_id'] ?? 1;
}

/**
 * Log API request for debugging
 * @param string $endpoint
 * @param array $data
 */
function logApiRequest($endpoint, $data = null) {
    $logFile = dirname(__DIR__) . '/logs/api.log';
    $logDir = dirname($logFile);
    
    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    $logEntry = array(
        'timestamp' => date('Y-m-d H:i:s'),
        'endpoint' => $endpoint,
        'method' => $_SERVER['REQUEST_METHOD'],
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'data' => $data
    );
    
    file_put_contents($logFile, json_encode($logEntry) . PHP_EOL, FILE_APPEND);
}

/**
 * Get department name by ID
 * @param int $id
 * @return string
 */
function getDepartmentName($id) {
    $departments = array(
        1 => "Super Admin",
        2 => "Finance Department",
        3 => "Sales & Marketing",
        4 => "Manager Department",
        5 => "Secretary Department",
        6 => "Bricks & Timber",
        7 => "Aluminium Department",
        8 => "Town Planning",
        9 => "Architectural Department",
        10 => "Survey Department",
        11 => "Construction Department",
        12 => "Hatimiliki Department"
    );
    
    return $departments[$id] ?? "Unknown Department";
}

// Initialize database connection for reuse
$database = null;
$db = null;

function getDB() {
    global $database, $db;
    
    if ($database === null) {
        $database = new Database();
        $db = $database->getConnection();
    }
    
    return $db;
}

// Start session only for endpoints that need it
$sessionEndpoints = array('login', 'logout', 'change_password', 'get_profile');
$currentScript = basename($_SERVER['SCRIPT_NAME'], '.php');

if (in_array($currentScript, $sessionEndpoints)) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}
?>