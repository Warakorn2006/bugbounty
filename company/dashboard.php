<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('company');

$company_id = $_SESSION['user_id'];

// Stats
$stats = [
    'active_campaigns' => $conn->query("SELECT COUNT(*) FROM campaigns WHERE company_id = $company_id AND status = 'active'")->fetch_row()[0],
    'total_reports' => $conn->query("SELECT COUNT(r.id) FROM reports r JOIN campaigns c ON r.campaign_id = c.id WHERE c.company_id = $company_id")->fetch_row()[0],
    'pending_reviews' => $conn->query("SELECT COUNT(r.id) FROM reports r JOIN campaigns c ON r.campaign_id = c.id WHERE c.company_id = $company_id AND r.status IN ('Pending', 'Triaged')")->fetch_row()[0],
    'resolved' => $conn->query("SELECT COUNT(r.id) FROM reports r JOIN campaigns c ON r.campaign_id = c.id WHERE c.company_id = $company_id AND r.status = 'Resolved'")->fetch_row()[0],
];

// Recent Reports
$reports = $conn->query("
    SELECT r.*, c.title as campaign_title, u.username as reporter 
    FROM reports r 
    JOIN campaigns c ON r.campaign_id = c.id 
    JOIN users u ON r.user_id = u.id 
    WHERE c.company_id = $company_id 
    ORDER BY r.created_at DESC LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Company Dashboard - BugBounty TH</title>
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
            <a href="<?php echo BASE_URL; ?>/company/dashboard.php" class="active">Dashboard</a>
            <a href="<?php echo BASE_URL; ?>/company/my_campaigns.php">My Campaigns</a>
            <a href="<?php echo BASE_URL; ?>/company/create_campaign.php">Create Campaign</a>
            <a href="<?php echo BASE_URL; ?>/company/reports.php">Reports Inbox</a>
            <a href="<?php echo BASE_URL; ?>/public/logout.php" style="color:var(--danger); margin-top:2rem;">Logout</a>
        </aside>
        <main class="main-content">
            <div class="page-header">
                <h1>Company Overview</h1>
                <p class="page-subtitle">Manage your bug bounty programs</p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Active Campaigns</div>
                    <div class="stat-value"><?php echo $stats['active_campaigns']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Reports Received</div>
                    <div class="stat-value"><?php echo $stats['total_reports']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Pending Reviews</div>
                    <div class="stat-value" style="color:var(--warning);"><?php echo $stats['pending_reviews']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Resolved Issues</div>
                    <div class="stat-value" style="color:var(--success);"><?php echo $stats['resolved']; ?></div>
                </div>
            </div>
            
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                    <h3 class="card-title" style="margin:0;">Recent Reports</h3>
                    <a href="<?php echo BASE_URL; ?>/company/reports.php" class="btn btn-outline">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Campaign</th>
                                <th>Reporter</th>
                                <th>Vulnerability</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($reports->num_rows == 0): ?>
                            <tr><td colspan="6" style="text-align:center; color:var(--text-secondary);">No reports received yet.</td></tr>
                            <?php endif; ?>
                            <?php while($r = $reports->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $r['id']; ?></td>
                                <td><?php echo e($r['campaign_title']); ?></td>
                                <td><?php echo e($r['reporter']); ?></td>
                                <td><?php echo get_severity_badge($r['severity']); ?> <?php echo e($r['vulnerability_type']); ?></td>
                                <td><?php echo get_status_badge($r['status']); ?></td>
                                <td><a href="<?php echo BASE_URL; ?>/company/review_report.php?id=<?php echo $r['id']; ?>" class="btn btn-primary" style="padding:0.25rem 0.5rem; font-size:0.75rem;">Review</a></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
