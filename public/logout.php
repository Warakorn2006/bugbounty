<?php
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['user_id'])) {
    log_action('logout_success', $_SESSION['user_id']);
}

session_unset();
session_destroy();
setcookie(session_name(), '', time() - 3600, '/');

header("Location: " . BASE_URL . "/public/login.php");
exit;
