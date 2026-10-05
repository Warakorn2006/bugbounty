<?php
require_once __DIR__ . '/config.php';

define('DB_HOST', 'sql206.infinityfree.com');
define('DB_USER', 'if0_43035286');
define('DB_PASS', 'ScxilctDLF');
define('DB_NAME', 'if0_43035286_bug');
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    error_log('DB Connection failed: ' . $conn->connect_error);
    die('<div style="font-family:system-ui;padding:40px;text-align:center;"><h2>⚠️ ระบบขัดข้องชั่วคราว</h2><p>กรุณาลองใหม่อีกครั้ง</p></div>');
}
$conn->set_charset('utf8mb4');
