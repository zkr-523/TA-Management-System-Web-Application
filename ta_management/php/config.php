<?php
/*
1. Establishes database connection
2. Starts the session
3. Provides helper functions for authentication/authorization

*/

// Database configuration (to define how php will connect to database)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ta_management";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session or resume php
session_start();

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check user role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] == $role;
}

// Function to redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../index.php");
        exit();
    }
}

// Function to redirect if not authorized for role
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header("Location: ../unauthorized.php");
        exit();
    }
}
?>
