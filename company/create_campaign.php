<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_role('company');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf_token($_POST['csrf_token'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $target_url = trim($_POST['target_url']);
        $scope = trim($_POST['scope']);
        $rules = trim($_POST['rules']);
        $reward_sqli = (int)$_POST['reward_sqli'];
        $reward_xss = (int)$_POST['reward_xss'];
        $reward_other = (int)$_POST['reward_other'];
        
        if (empty($title) || empty($description) || empty($target_url) || empty($scope)) {
            $error = 'Please fill in all required fields.';
        } else {
            $stmt = $conn->prepare("INSERT INTO campaigns (company_id, title, description, target_url, scope, rules, reward_sqli, reward_xss, reward_other, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param("isssssiii", $_SESSION['user_id'], $title, $description, $target_url, $scope, $rules, $reward_sqli, $reward_xss, $reward_other);
            if ($stmt->execute()) {
                log_action('campaign_created', $_SESSION['user_id'], "Campaign: $title");
                $success = 'Campaign created successfully! It is pending admin approval.';
            } else {
                $error = 'Failed to create campaign.';
            }
        }
    } else {
        $error = 'Invalid token.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Campaign - BugBounty TH</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">🐛 BugBounty<span>TH</span> <small style="color:var(--text-secondary);font-weight:normal;">| Company</small></div>
        <div class="nav-user">
            <div class="avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 2)); ?></div>
            <?php echo e($_SESSION['username']); ?>
        </div>
    </nav>
    <div class="app-layout">
        <aside class="sidebar">
            <a href="<?php echo BASE_URL; ?>/company/dashboard.php">Dashboard</a>
            <a href="<?php echo BASE_URL; ?>/company/my_campaigns.php">My Campaigns</a>
            <a href="<?php echo BASE_URL; ?>/company/create_campaign.php" class="active">Create Campaign</a>
            <a href="<?php echo BASE_URL; ?>/company/reports.php">Reports Inbox</a>
            <a href="<?php echo BASE_URL; ?>/public/logout.php" style="color:var(--danger); margin-top:2rem;">Logout</a>
        </aside>
        <main class="main-content">
            <div class="page-header">
                <h1>Create New Campaign</h1>
                <p class="page-subtitle">Launch a new bug bounty program</p>
            </div>
            
            <div class="card" style="max-width:800px;">
                <?php if($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>
                <?php if($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php else: ?>
                
                <form method="post">
                    <?php echo csrf_field(); ?>
                    
                    <div class="form-group">
                        <label>Campaign Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" required placeholder="Describe the purpose of this bug hunt..."></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Target URL</label>
                            <input type="url" name="target_url" class="form-control" required placeholder="https://...">
                        </div>
                        <div class="form-group">
                            <label>In-Scope Targets (comma separated)</label>
                            <input type="text" name="scope" class="form-control" required placeholder="*.example.com, api.example.com">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Rules & Out of Scope</label>
                        <textarea name="rules" class="form-control" placeholder="E.g., No DoS/DDoS, no social engineering..."></textarea>
                    </div>
                    
                    <h3 style="margin:2rem 0 1rem;">Reward Structure (Points)</h3>
                    <div class="form-row" style="grid-template-columns: 1fr 1fr 1fr;">
                        <div class="form-group">
                            <label>SQL Injection (Max)</label>
                            <input type="number" name="reward_sqli" class="form-control" value="1000" required>
                        </div>
                        <div class="form-group">
                            <label>XSS (Max)</label>
                            <input type="number" name="reward_xss" class="form-control" value="500" required>
                        </div>
                        <div class="form-group">
                            <label>Other (Max)</label>
                            <input type="number" name="reward_other" class="form-control" value="200" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="margin-top:1rem; font-size:1.125rem; padding:0.75rem 2rem;">Submit for Approval</button>
                </form>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
