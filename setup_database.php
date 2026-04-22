<?php
// setup_database.php - Reset and setup fresh database

$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'geotraverse_db';

echo "<h1>GeoTraverse Database Setup</h1>";

try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Drop existing database
    echo "<p>🗑️ Dropping existing database...</p>";
    $pdo->exec("DROP DATABASE IF EXISTS $dbname");
    echo "<p style='color:green'>✅ Database dropped successfully!</p>";
    
    // Create new database
    echo "<p>📁 Creating new database...</p>";
    $pdo->exec("CREATE DATABASE $dbname");
    echo "<p style='color:green'>✅ Database created successfully!</p>";
    
    // Select database
    $pdo->exec("USE $dbname");
    
    // Create tables
    echo "<p>📋 Creating tables...</p>";
    
    // Users table
    $pdo->exec("CREATE TABLE users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        email VARCHAR(255) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        full_name VARCHAR(255) NOT NULL,
        role ENUM('admin','manager','finance','sales','secretary') DEFAULT 'manager',
        is_active TINYINT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Employees table
    $pdo->exec("CREATE TABLE employees (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        phone VARCHAR(50),
        password_hash VARCHAR(255),
        role VARCHAR(100),
        department VARCHAR(100),
        salary DECIMAL(12,2),
        status ENUM('active','inactive') DEFAULT 'active',
        user_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )");
    
    // Projects table
    $pdo->exec("CREATE TABLE projects (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        client_name VARCHAR(255),
        amount DECIMAL(15,2),
        status VARCHAR(50) DEFAULT 'pending',
        progress INT DEFAULT 0,
        location VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Tasks table
    $pdo->exec("CREATE TABLE tasks (
        id INT PRIMARY KEY AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        department VARCHAR(100),
        completed TINYINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Messages table
    $pdo->exec("CREATE TABLE messages (
        id INT PRIMARY KEY AUTO_INCREMENT,
        from_department VARCHAR(100) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT DEFAULT 0,
        reply_message TEXT,
        replied_at DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Reports table
    $pdo->exec("CREATE TABLE reports (
        id INT PRIMARY KEY AUTO_INCREMENT,
        from_department VARCHAR(100) NOT NULL,
        report_type VARCHAR(100) NOT NULL,
        report_data TEXT,
        message TEXT,
        is_read TINYINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Activity logs table
    $pdo->exec("CREATE TABLE activity_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        action VARCHAR(255) NOT NULL,
        user_email VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Sessions table
    $pdo->exec("CREATE TABLE sessions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        token VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Password resets table
    $pdo->exec("CREATE TABLE password_resets (
        id INT PRIMARY KEY AUTO_INCREMENT,
        email VARCHAR(255) NOT NULL,
        token VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_token (token),
        INDEX idx_email (email)
    )");
    
    echo "<p style='color:green'>✅ All tables created successfully!</p>";
    
    // Insert admin account
    echo "<p>👤 Creating admin account...</p>";
    $adminEmail = 'jacksonmyula773@gmail.com';
    $adminPassword = '1234';
    $adminHash = password_hash($adminPassword, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, full_name, role, is_active) VALUES (?, ?, 'Jackson Myula', 'admin', 1)");
    $stmt->execute([$adminEmail, $adminHash]);
    
    // Insert manager account
    $managerEmail = 'manager@geotraverse.com';
    $managerPassword = '1234';
    $managerHash = password_hash($managerPassword, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, full_name, role, is_active) VALUES (?, ?, 'John Manager', 'manager', 1)");
    $stmt->execute([$managerEmail, $managerHash]);
    $managerId = $pdo->lastInsertId();
    
    // Insert employee record for manager
    $stmt = $pdo->prepare("INSERT INTO employees (name, email, phone, role, department, salary, status, user_id) VALUES (?, ?, '0712345678', 'Manager', 'Management', '2500000', 'active', ?)");
    $stmt->execute(['John Manager', $managerEmail, $managerId]);
    
    // Insert sample messages
    $stmt = $pdo->prepare("INSERT INTO messages (from_department, subject, message) VALUES 
        ('Managers', 'Project Update', 'We have completed the Modern Villa project in Kigamboni.'),
        ('Finance', 'Invoice Payment', 'Client John Doe has paid TZS 5,000,000.'),
        ('Sales', 'New Lead', 'New customer interested in 5-acre land survey.'),
        ('Secretary', 'Meeting Request', 'Please confirm board meeting schedule for next week.')");
    $stmt->execute();
    
    // Insert sample activity
    $stmt = $pdo->prepare("INSERT INTO activity_logs (action, user_email) VALUES ('Database setup completed', 'system@geotraverse.com')");
    $stmt->execute();
    
    echo "<p style='color:green'>✅ Admin account created successfully!</p>";
    
    echo "<hr>";
    echo "<h2>✅ Database Setup Complete!</h2>";
    echo "<h3>Login Credentials:</h3>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>Portal</th><th>Email</th><th>Password</th></tr>";
    echo "<tr><td>Admin</td><td><strong>jacksonmyula773@gmail.com</strong></td><td><strong>1234</strong></td></tr>";
    echo "<tr><td>Manager</td><td><strong>manager@geotraverse.com</strong></td><td><strong>1234</strong></td></tr>";
    echo "</table>";
    echo "<br>";
    echo "<a href='/geotraverse/admin/login.html' style='background: #0f74ba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Admin Login →</a>";
    
} catch(PDOException $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}
?>