<?php
session_start();
$host = 'localhost';
$user = 'root';
$pass = '';
$dbName = 'invoicebill';

$mysqli = new mysqli($host, $user, $pass);
if (!$mysqli->connect_errno) {
    $mysqli->query("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $mysqli->select_db($dbName);

    // Create users table
    $mysqli->query("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'user') DEFAULT 'user',
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        subscription_end_date DATE NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");


    // Create default admin if not exists
    $result = $mysqli->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    if ($result && $result->num_rows == 0) {
        $hashed_password = password_hash('admin', PASSWORD_DEFAULT);
        $mysqli->query("INSERT INTO users (email, password, role, status, subscription_end_date) VALUES ('aslampc406@gmail.com', '$hashed_password', 'admin', 'approved', '2099-12-31')");
    } else {
        // Update existing admin to new email just in case it was already created as admin@admin.com
        $mysqli->query("UPDATE users SET email = 'aslampc406@gmail.com' WHERE role = 'admin'");
    }
} else {
    die("Database connection failed");
}

function check_auth() {
    global $mysqli;
    if (!isset($_SESSION['user_id'])) {
        // Automatically login as the primary admin user to bypass login
        $result = $mysqli->query("SELECT id, email, role FROM users WHERE role = 'admin' LIMIT 1");
        if ($result && $user = $result->fetch_assoc()) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
        } else {
            $_SESSION['user_id'] = 0;
            $_SESSION['role'] = 'admin';
            $_SESSION['email'] = 'admin@example.com';
        }
    }

    // Ensure bills table has user_id
    $result = $mysqli->query("SHOW COLUMNS FROM bills LIKE 'user_id'");
    if ($result && $result->num_rows === 0) {
        // If table doesn't exist yet, it will fail silently, which is fine since save_bill creates it
        $mysqli->query("ALTER TABLE bills ADD COLUMN user_id INT DEFAULT 0");
    }
}

function check_admin() {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        die("Unauthorized access.");
    }
}
?>
