<?php
// database.php - Database connection and setup

require_once 'config.php';

class Database {
    private $conn;

    public function __construct() {
        $this->connect();
        $this->createDatabase();
        $this->createTables();
        $this->createDefaultAdmin();
    }

    private function connect() {
        $this->conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD);
        
        if ($this->conn->connect_error) {
            throw new Exception('Database connection failed: ' . $this->conn->connect_error);
        }
    }

    private function createDatabase() {
        $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
        if (!$this->conn->query($sql)) {
            throw new Exception('Failed to create database: ' . $this->conn->error);
        }
        
        if (!$this->conn->select_db(DB_NAME)) {
            throw new Exception('Failed to select database: ' . $this->conn->error);
        }
    }

    private function createTables() {
        // Create notifications table
        $notificationsTable = "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
            category VARCHAR(100) DEFAULT 'general',
            valid_until DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_by VARCHAR(100),
            folder_path VARCHAR(500),
            status ENUM('active', 'inactive') DEFAULT 'active'
        )";

        if (!$this->conn->query($notificationsTable)) {
            throw new Exception('Failed to create notifications table: ' . $this->conn->error);
        }

        // Create notification_files table
        $filesTable = "CREATE TABLE IF NOT EXISTS notification_files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            notification_id INT,
            original_name VARCHAR(255),
            saved_name VARCHAR(255),
            file_path VARCHAR(500),
            file_size INT,
            file_type VARCHAR(100),
            upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE
        )";

        if (!$this->conn->query($filesTable)) {
            throw new Exception('Failed to create notification_files table: ' . $this->conn->error);
        }

        // Create admin_users table
        $usersTable = "CREATE TABLE IF NOT EXISTS admin_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(200),
            role ENUM('admin', 'assistant') DEFAULT 'assistant',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        if (!$this->conn->query($usersTable)) {
            throw new Exception('Failed to create admin_users table: ' . $this->conn->error);
        }

        // Create faculty table
        $facultyTable = "CREATE TABLE IF NOT EXISTS faculty (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            designation VARCHAR(255),
            department ENUM('arts', 'science', 'computer_science') NOT NULL,
            qualification VARCHAR(500),
            specialization VARCHAR(500),
            experience_years INT DEFAULT 0,
            email VARCHAR(255),
            phone VARCHAR(20),
            profile_image VARCHAR(500),
            bio TEXT,
            research_interests TEXT,
            publications TEXT,
            display_order INT DEFAULT 0,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";

        if (!$this->conn->query($facultyTable)) {
            throw new Exception('Failed to create faculty table: ' . $this->conn->error);
        }
    }

    private function createDefaultAdmin() {
        $check_admin = $this->conn->query("SELECT * FROM admin_users WHERE username = '" . DEFAULT_ADMIN_USER . "'");
        
        if ($check_admin->num_rows == 0) {
            $admin_password = password_hash(DEFAULT_ADMIN_PASS, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("INSERT INTO admin_users (username, password, full_name, role) VALUES (?, ?, ?, 'admin')");
            $stmt->bind_param("sss", DEFAULT_ADMIN_USER, $admin_password, DEFAULT_ADMIN_NAME);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to create default admin user: ' . $stmt->error);
            }
        }
    }

    public function getConnection() {
        return $this->conn;
    }

    public function query($sql) {
        return $this->conn->query($sql);
    }

    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }

    public function escape_string($string) {
        return $this->conn->real_escape_string($string);
    }

    public function insert_id() {
        return $this->conn->insert_id;
    }

    public function error() {
        return $this->conn->error;
    }

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

// Initialize database connection
try {
    $db = new Database();
} catch (Exception $e) {
    if (DEBUG_MODE) {
        die('Database initialization failed: ' . $e->getMessage());
    } else {
        die('Database connection error. Please try again later.');
    }
}
?>