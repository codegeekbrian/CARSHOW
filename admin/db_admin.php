<?php
session_start();

// Include the main database connection
require_once dirname(__DIR__) . '/include/db.php';

// Include admin configuration
require_once 'config.php';

// Function to check if user is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Function to redirect to login page if not authenticated
function requireAdminAuth() {
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Function to authenticate admin
function authenticateAdmin($username, $password) {
    if ($username === ADMIN_USERNAME && password_verify($password, ADMIN_PASSWORD_HASH)) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        return true;
    }
    return false;
}

// Function to logout admin
function logoutAdmin() {
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_username']);
    session_destroy();
}
?>