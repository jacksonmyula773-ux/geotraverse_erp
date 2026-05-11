<?php
// backend/config/database.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

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
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8mb4");
        } catch(PDOException $exception) {
            echo json_encode(["success" => false, "message" => "Connection error: " . $exception->getMessage()]);
            exit();
        }
        return $this->conn;
    }
}

function sendResponse($success, $data = null, $message = "", $unviewed_count = null) {
    $response = ["success" => $success];
    if ($data !== null) $response["data"] = $data;
    if ($message !== "") $response["message"] = $message;
    if ($unviewed_count !== null) $response["unviewed_count"] = $unviewed_count;
    echo json_encode($response);
    exit();
}

session_start();
?>