<?php
// backend/config/database.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON headers - HII NI MUHIMU SANA
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
}

/**
 * Send JSON response
 */
function sendResponse($success, $data = null, $message = "", $unviewed_count = null) {
    $response = array("success" => $success);
    if ($data !== null) $response["data"] = $data;
    if ($message !== "") $response["message"] = $message;
    if ($unviewed_count !== null) $response["unviewed_count"] = $unviewed_count;
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();
?>