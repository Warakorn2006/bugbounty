<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('user');

$search = $_GET['search'] ?? '';

if (!empty($search)) {
    $stmt = $conn->prepare("SELECT c.*, u.company_name FROM campaigns c JOIN users u ON c.company_id = u.id WHERE c.status='active' AND (c.title LIKE ? OR c.description LIKE ? OR u.company_name LIKE ?) ORDER BY c.created_at DESC");
    $search_like = "%$search%";
    $stmt->bind_param("sss", $search_like, $search_like, $search_like);
    $stmt->execute();
    $campaigns = $stmt->get_result();
} else {
    $campaigns = $conn->query("SELECT c.*, u.company_name FROM campaigns c JOIN users u ON c.company_id = u.id WHERE c.status='active' ORDER BY c.created_at DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Campaigns - BugBounty TH</title>
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
            <a href="<?php echo BASE_URL; ?>/user/campaigns.php" class="active">Browse Campaigns</a>
            <a href="<?php echo BASE_URL; ?>/user/my_reports.php">My Reports</a>
            <a href="<?php echo BASE_URL; ?>/user/submit_report.php">Submit Report</a>
            <a href="<?php echo BASE_URL; ?>/user/redeem.php">Reward Store</a>
            <a href="<?php echo BASE_URL; ?>/public/logout.php" style="color:var(--danger); margin-top:2rem;">Logout</a>
        </aside>
        <main class="main-content">
            <div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h1>Active Campaigns</h1>
                    <p class="page-subtitle">Find your next target and start hunting</p>
                </div>
                <form method="get" style="display:flex; gap:0.5rem;">
                    <input type="text" name="search" class="form-control" placeholder="Search campaigns..." value="<?php echo e($search); ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>
            
            <div class="grid-3">
                <?php while($c = $campaigns->fetch_assoc()): ?>
                <div class="card campaign-card">
                    <div class="card-header">
                        <div class="company-logo"><?php echo substr($c['company_name'], 0, 2); ?></div>
                        <div>
                            <h3 class="card-title"><?php echo e($c['title']); ?></h3>
                            <p class="card-subtitle"><?php echo e($c['company_name']); ?></p>
                        </div>
                    </div>
                    <p class="card-desc"><?php echo e(substr($c['description'], 0, 120)); ?>...</p>
                    <div class="reward-tags">
                        <span class="tag tag-critical">SQLi: <?php echo format_points($c['reward_sqli']); ?></span>
                        <span class="tag tag-high">XSS: <?php echo format_points($c['reward_xss']); ?></span>
                        <span class="tag tag-medium">Other: <?php echo format_points($c['reward_other']); ?></span>
                    </div>
                    <div class="card-footer">
                        <span class="meta">Target: <?php echo e($c['target_url']); ?></span>
                        <a href="<?php echo BASE_URL; ?>/user/submit_report.php?campaign_id=<?php echo $c['id']; ?>" class="btn btn-primary">Report Bug</a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            
            <?php if($campaigns->num_rows == 0): ?>
            <div class="card" style="text-align:center; padding:3rem;">
                <p style="color:var(--text-secondary); font-size:1.125rem;">No campaigns found.</p>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
