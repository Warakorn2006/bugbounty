<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('company');

$company_id = $_SESSION['user_id'];
$campaigns = $conn->query("
    SELECT c.*, 
    (SELECT COUNT(*) FROM reports WHERE campaign_id = c.id) as report_count,
    (SELECT COUNT(*) FROM reports WHERE campaign_id = c.id AND status IN ('Pending', 'Triaged')) as pending_count
    FROM campaigns c 
    WHERE c.company_id = $company_id 
    ORDER BY c.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Campaigns - BugBounty TH</title>
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
            <a href="<?php echo BASE_URL; ?>/company/my_campaigns.php" class="active">My Campaigns</a>
            <a href="<?php echo BASE_URL; ?>/company/create_campaign.php">Create Campaign</a>
            <a href="<?php echo BASE_URL; ?>/company/reports.php">Reports Inbox</a>
            <a href="<?php echo BASE_URL; ?>/public/logout.php" style="color:var(--danger); margin-top:2rem;">Logout</a>
        </aside>
        <main class="main-content">
            <div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h1>My Campaigns</h1>
                    <p class="page-subtitle">Manage your programs</p>
                </div>
                <a href="<?php echo BASE_URL; ?>/company/create_campaign.php" class="btn btn-primary">Create New</a>
            </div>
            
            <div class="card">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Target</th>
                                <th>Status</th>
                                <th>Reports</th>
                                <th>Pending</th>
                                <th>Date Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($campaigns->num_rows == 0): ?>
                            <tr><td colspan="6" style="text-align:center; color:var(--text-secondary);">No campaigns found.</td></tr>
                            <?php endif; ?>
                            <?php while($c = $campaigns->fetch_assoc()): ?>
                            <tr>
                                <td style="font-weight:500;"><?php echo e($c['title']); ?></td>
                                <td><a href="<?php echo e($c['target_url']); ?>" target="_blank" style="font-size:0.875rem;"><?php echo e($c['target_url']); ?></a></td>
                                <td><?php echo get_status_badge($c['status']); ?></td>
                                <td><?php echo $c['report_count']; ?></td>
                                <td style="color:var(--warning); font-weight:bold;"><?php echo $c['pending_count'] > 0 ? $c['pending_count'] : 0; ?></td>
                                <td><?php echo date('d M Y', strtotime($c['created_at'])); ?></td>
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
