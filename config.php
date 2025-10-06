<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ecommerce_db');

// Create database connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL
define('BASE_URL', 'http://localhost/ecommerce/');

// Upload directory
define('UPLOAD_DIR', 'uploads/');

// Helper Functions
function redirect($page) {
    header("Location: " . $page);
    exit();
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function isUserLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        redirect('admin/login.php');
    }
}

function requireUser() {
    if (!isUserLoggedIn()) {
        redirect('login.php');
    }
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatPrice($price) {
    return '$' . number_format($price, 2);
}
?>