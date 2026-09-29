<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/" . $_SESSION['role'] . "/dashboard.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } else {
        $stmt = $conn->prepare("SELECT id, password, role, status FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
            $user = $res->fetch_assoc();
            if ($user['status'] === 'banned') {
                $error = 'บัญชีของคุณถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ';
                log_action('login_failed_banned', $user['id'], "Banned account login attempt");
            } elseif (password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();
                log_action('login_success', $user['id'], "Successful login");
                header("Location: " . BASE_URL . "/" . $user['role'] . "/dashboard.php");
                exit;
            } else {
                $error = 'Username หรือ Password ไม่ถูกต้อง';
                log_action('login_failed', $user['id'], "Invalid password attempt for: $username");
            }
        } else {
            $error = 'Username หรือ Password ไม่ถูกต้อง';
            log_action('login_failed', null, "Unknown username: $username");
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — BugBountyTH</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-box">
            <div class="auth-logo">
                <div class="brand">🐛 BugBounty<span>TH</span></div>
            </div>
            <h2 class="auth-title">ยินดีต้อนรับกลับ</h2>
            <p class="auth-sub">เข้าสู่ระบบเพื่อดำเนินการต่อ</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo e($error); ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['msg'])): ?>
                <?php if ($_GET['msg'] === 'timeout'): ?>
                    <div class="alert alert-warning">⏱ Session หมดอายุแล้ว กรุณา Login ใหม่</div>
                <?php elseif ($_GET['msg'] === 'unauthorized'): ?>
                    <div class="alert alert-danger">⛔ คุณไม่มีสิทธิ์เข้าถึงหน้านี้</div>
                <?php endif; ?>
            <?php endif; ?>

            <form method="post" action="">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control"
                           placeholder="your_username" required autofocus autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="••••••••" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:0.65rem;font-size:0.9375rem;">
                    เข้าสู่ระบบ →
                </button>
            </form>

            <p class="text-center mt-2 text-sm text-muted">
                ยังไม่มีบัญชี? <a href="<?php echo BASE_URL; ?>/public/register.php">สมัครสมาชิก</a>
            </p>
            <p class="text-center mt-1 text-sm text-muted">
                <a href="<?php echo BASE_URL; ?>/public/index.php">← กลับหน้าหลัก</a>
            </p>
        </div>
    </div>
</body>
</html>
