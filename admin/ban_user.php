<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf_token($_POST['csrf_token'])) {
        $user_id = (int)$_POST['user_id'];
        $action = $_POST['action'];
        
        $status = $action === 'ban' ? 'banned' : 'active';
        
        // Ensure not banning self
        if ($user_id !== $_SESSION['user_id']) {
            $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ? AND role != 'admin'");
            $stmt->bind_param("si", $status, $user_id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                log_action("user_$action", $_SESSION['user_id'], "Target User ID: $user_id");
                $_SESSION['flash_msg'] = "User has been $status.";
            }
        }
    }
}

header("Location: " . BASE_URL . "/admin/users.php");
exit;
