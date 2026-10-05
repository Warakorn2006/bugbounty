<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.cookie_httponly', '1');
    session_start();
}
/**
 * BugBountyTH - Central Configuration
 * แก้ค่า BASE_URL ให้ตรงกับ environment ที่ใช้งาน
 */

// ============================
// URL Configuration
// ============================
// หากใช้ XAMPP: define('BASE_URL', '/bugbounty');
// หากใช้ InfinityFree (root domain): define('BASE_URL', '');
define('BASE_URL', '');

// ============================
// Asset Path Helper
// ============================
define('CSS_PATH', BASE_URL . '/assets/css/style.css');
define('UPLOAD_WEB_PATH', BASE_URL . '/assets/uploads/');

// ============================
// App Settings
// ============================
define('APP_NAME', 'BugBountyTH');
define('APP_VERSION', '1.0.0');
define('SESSION_TIMEOUT', 1800); // 30 minutes

// ============================
// File Upload Settings
// ============================
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'application/pdf']);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);
