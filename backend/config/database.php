<?php
/**
 * GeoTraverse ERP - Database Configuration
 */

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
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->exec("SET NAMES utf8mb4");
        } catch(PDOException $exception) {
            // Log error but don't expose to client
            error_log("Connection error: " . $exception->getMessage());
            // For development only
            if ( $_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_ADDR'] === '127.0.0.1') {
                die(json_encode(["success" => false, "error" => "Database connection failed: " . $exception->getMessage()]));
            }
            die(json_encode(["success" => false, "error" => "Database connection failed"]));
        }
        return $this->conn;
    }
}

// Global helper function
function getDB() {
    $database = new Database();
    return $database->getConnection();
}
?>