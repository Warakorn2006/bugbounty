<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

if (isset($_SESSION['user_id'])) {
    header("Location: /" . $_SESSION['role'] . "/dashboard.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    $company_name = trim($_POST['company_name'] ?? '');

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!in_array($role, ['user', 'company'])) {
        $error = 'Invalid role selected.';
    } elseif ($role === 'company' && empty($company_name)) {
        $error = 'Company name is required for company accounts.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Username or email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, company_name) VALUES (?, ?, ?, ?, ?)");
            $cn = $role === 'company' ? $company_name : null;
            $stmt->bind_param("sssss", $username, $email, $hashed, $role, $cn);
            
            if ($stmt->execute()) {
                log_action('register_success', $stmt->insert_id, "Registered as $role");
                $success = 'Registration successful! You can now <a href="<?php echo BASE_URL; ?>/public/login.php">log in</a>.';
            } else {
                $error = 'Registration failed due to a system error.';
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - BugBounty TH</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        .auth-container { max-width: 500px; margin: 4rem auto; }
        #company_name_group { display: none; }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="card">
            <h2 style="text-align:center;margin-bottom:1.5rem;">🐛 BugBounty<span>TH</span></h2>
            <h3 style="margin-bottom:1.5rem;text-align:center;">Create an account</h3>
            
            <?php if($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>
            <?php if($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php else: ?>
            
            <form method="post" action="">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="role" class="form-control" onchange="toggleCompany()">
                        <option value="user" <?php echo (isset($_GET['role']) && $_GET['role']==='company') ? '' : 'selected'; ?>>Security Researcher</option>
                        <option value="company" <?php echo (isset($_GET['role']) && $_GET['role']==='company') ? 'selected' : ''; ?>>Company</option>
                    </select>
                </div>
                <div class="form-group" id="company_name_group">
                    <label>Company Name</label>
                    <input type="text" name="company_name" class="form-control">
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">Register</button>
            </form>
            <?php endif; ?>
            <p style="text-align:center;margin-top:1.5rem;color:var(--text-secondary);">
                Already have an account? <a href="<?php echo BASE_URL; ?>/public/login.php">Log in</a>
            </p>
        </div>
    </div>
    
    <script>
        function toggleCompany() {
            var role = document.getElementById('role').value;
            var cgroup = document.getElementById('company_name_group');
            if(role === 'company') {
                cgroup.style.display = 'block';
            } else {
                cgroup.style.display = 'none';
            }
        }
        toggleCompany();
    </script>
</body>
</html>
