<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.cookie_httponly', '1');
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/public/login.php");
    exit;
}

// Timeout after SESSION_TIMEOUT idle
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    header("Location: " . BASE_URL . "/public/login.php?msg=timeout");
    exit;
}
$_SESSION['last_activity'] = time();

// Check role function
function require_role($required_role) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $required_role) {
        header("Location: " . BASE_URL . "/public/login.php?msg=unauthorized");
        exit;
    }
}
