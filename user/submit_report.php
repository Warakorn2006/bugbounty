<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_role('user');

$campaigns = $conn->query("SELECT id, title, company_id FROM campaigns WHERE status='active' ORDER BY title ASC");

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campaign_id = (int)($_POST['campaign_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $type = $_POST['vulnerability_type'] ?? '';
    $severity = $_POST['severity'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $steps = trim($_POST['steps_to_reproduce'] ?? '');
    $impact = trim($_POST['impact'] ?? '');
    
    $allowed_types = ['SQL Injection','XSS','CSRF','RCE','LFI','IDOR','Other'];
    $allowed_severities = ['Low','Medium','High','Critical'];
    
    // Campaign must exist and be active
    $stmt_c = $conn->prepare("SELECT id FROM campaigns WHERE id = ? AND status = 'active'");
    $stmt_c->bind_param("i", $campaign_id);
    $stmt_c->execute();
    $campaign_ok = $stmt_c->get_result()->num_rows === 1;
    $stmt_c->close();
    
    if (empty($campaign_id) || empty($title) || empty($description) || empty($steps)) {
        $error = 'Please fill in all required fields.';
    } elseif (!$campaign_ok) {
        $error = 'Selected campaign is not available.';
    } elseif (mb_strlen($title) > 200) {
        $error = 'Title is too long (max 200 characters).';
    } elseif (!in_array($type, $allowed_types) || !in_array($severity, $allowed_severities)) {
        $error = 'Invalid vulnerability type or severity.';
    } else {
        $poc_path = null;
        if (isset($_FILES['poc_file']) && $_FILES['poc_file']['error'] == UPLOAD_ERR_OK) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['poc_file']['tmp_name']);
            $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
            $ext = strtolower(pathinfo($_FILES['poc_file']['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
            
            if (!in_array($mime, $allowed_mimes) || !in_array($ext, $allowed_exts)) {
                $error = 'Invalid file type. Only JPG, PNG, GIF, and PDF are allowed.';
            } elseif ($_FILES['poc_file']['size'] > 5 * 1024 * 1024) {
                $error = 'File too large. Max 5MB.';
            } else {
                $new_name = bin2hex(random_bytes(16)) . '.' . $ext;
                if (!is_dir(UPLOAD_PATH)) { mkdir(UPLOAD_PATH, 0755, true); }
                if (move_uploaded_file($_FILES['poc_file']['tmp_name'], UPLOAD_PATH . $new_name)) {
                    $poc_path = $new_name;
                } else {
                    $error = 'Failed to save the uploaded file. Please try again.';
                }
            }
        } elseif (isset($_FILES['poc_file']) && $_FILES['poc_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $error = 'File upload failed (file may be too large).';
        }
        
        if (empty($error)) {
            $stmt = $conn->prepare("INSERT INTO reports (campaign_id, user_id, title, vulnerability_type, severity, description, steps_to_reproduce, impact, poc_file) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisssssss", $campaign_id, $_SESSION['user_id'], $title, $type, $severity, $description, $steps, $impact, $poc_path);
            if ($stmt->execute()) {
                log_action('report_submitted', $_SESSION['user_id'], "Report ID: " . $stmt->insert_id);
                $_SESSION['flash_msg'] = "Report submitted successfully!";
                header("Location: " . BASE_URL . "/user/my_reports.php");
                exit;
            } else {
                $error = 'Failed to submit report. Please try again.';
            }
        }
    }
}

$preselect_campaign = isset($_GET['campaign_id']) ? (int)$_GET['campaign_id'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Report - BugBounty TH</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">🐛 BugBounty<span>TH</span></div>
        <div class="nav-user">
            <span class="points-badge">⚡ <?php echo number_format($_SESSION['points'] ?? 0); ?> pts</span>
            <div class="avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 2)); ?></div>
            <?php echo e($_SESSION['username']); ?>
        </div>
    </nav>
    <div class="app-layout">
        <aside class="sidebar">
            <a href="<?php echo BASE_URL; ?>/user/dashboard.php">Dashboard</a>
            <a href="<?php echo BASE_URL; ?>/user/campaigns.php">Browse Campaigns</a>
            <a href="<?php echo BASE_URL; ?>/user/my_reports.php">My Reports</a>
            <a href="<?php echo BASE_URL; ?>/user/submit_report.php" class="active">Submit Report</a>
            <a href="<?php echo BASE_URL; ?>/user/redeem.php">Reward Store</a>
            <a href="<?php echo BASE_URL; ?>/public/logout.php" style="color:var(--danger); margin-top:2rem;">Logout</a>
        </aside>
        <main class="main-content">
            <div class="page-header">
                <h1>Submit Vulnerability Report</h1>
                <p class="page-subtitle">Found a bug? Detail it below to help secure the system.</p>
            </div>
            
            <div class="card" style="max-width: 800px;">
                <?php if($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>
                
                <form method="post" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    
                    <div class="form-group">
                        <label>Campaign / Target Program</label>
                        <select name="campaign_id" class="form-control" required>
                            <option value="">-- Select Campaign --</option>
                            <?php while($c = $campaigns->fetch_assoc()): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo $preselect_campaign === (int)$c['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($c['title']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Report Title</label>
                        <input type="text" name="title" class="form-control" required placeholder="e.g. Stored XSS in User Profile Name">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Vulnerability Type</label>
                            <select name="vulnerability_type" class="form-control" required>
                                <option value="SQL Injection">SQL Injection</option>
                                <option value="XSS">Cross-Site Scripting (XSS)</option>
                                <option value="CSRF">Cross-Site Request Forgery (CSRF)</option>
                                <option value="RCE">Remote Code Execution (RCE)</option>
                                <option value="LFI">Local File Inclusion (LFI)</option>
                                <option value="IDOR">Insecure Direct Object Reference (IDOR)</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Severity</label>
                            <select name="severity" class="form-control" required>
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                                <option value="Critical">Critical</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" required placeholder="Describe the vulnerability in detail..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Steps to Reproduce</label>
                        <textarea name="steps_to_reproduce" class="form-control" required placeholder="1. Go to...\n2. Input...\n3. Observe..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Business Impact</label>
                        <textarea name="impact" class="form-control" placeholder="What can an attacker achieve?"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Proof of Concept (Optional)</label>
                        <input type="file" name="poc_file" class="form-control" accept=".jpg,.jpeg,.png,.gif,.pdf">
                        <small style="color:var(--text-secondary);">Allowed: JPG, PNG, GIF, PDF. Max: 5MB.</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="font-size:1.125rem; padding:0.75rem 2rem;">Submit Report</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
