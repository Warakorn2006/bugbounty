<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('company');

$company_id = $_SESSION['user_id'];
$status_filter = $_GET['status'] ?? '';
$severity_filter = $_GET['severity'] ?? '';

$sql = "
    SELECT r.*, c.title as campaign_title, u.username as reporter 
    FROM reports r 
    JOIN campaigns c ON r.campaign_id = c.id 
    JOIN users u ON r.user_id = u.id 
    WHERE c.company_id = $company_id
";

if ($status_filter) { $sql .= " AND r.status = '" . $conn->real_escape_string($status_filter) . "'"; }
if ($severity_filter) { $sql .= " AND r.severity = '" . $conn->real_escape_string($severity_filter) . "'"; }

$sql .= " ORDER BY CASE WHEN r.status = 'Pending' THEN 1 WHEN r.status = 'Triaged' THEN 2 ELSE 3 END, r.created_at DESC";

$reports = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports Inbox - BugBounty TH</title>
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
            <a href="<?php echo BASE_URL; ?>/company/create_campaign.php">Create Campaign</a>
            <a href="<?php echo BASE_URL; ?>/company/reports.php" class="active">Reports Inbox</a>
            <a href="<?php echo BASE_URL; ?>/public/logout.php" style="color:var(--danger); margin-top:2rem;">Logout</a>
        </aside>
        <main class="main-content">
            <div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-end;">
                <div>
                    <h1>Reports Inbox</h1>
                    <p class="page-subtitle">Review and manage vulnerability reports</p>
                </div>
                
                <form method="get" style="display:flex; gap:1rem;">
                    <select name="status" class="form-control" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?php echo $status_filter=='Pending'?'selected':''; ?>>Pending</option>
                        <option value="Triaged" <?php echo $status_filter=='Triaged'?'selected':''; ?>>Triaged</option>
                        <option value="Resolved" <?php echo $status_filter=='Resolved'?'selected':''; ?>>Resolved</option>
                    </select>
                    <select name="severity" class="form-control" onchange="this.form.submit()">
                        <option value="">All Severities</option>
                        <option value="Critical" <?php echo $severity_filter=='Critical'?'selected':''; ?>>Critical</option>
                        <option value="High" <?php echo $severity_filter=='High'?'selected':''; ?>>High</option>
                        <option value="Medium" <?php echo $severity_filter=='Medium'?'selected':''; ?>>Medium</option>
                        <option value="Low" <?php echo $severity_filter=='Low'?'selected':''; ?>>Low</option>
                    </select>
                </form>
            </div>
            
            <div class="card">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Campaign</th>
                                <th>Reporter</th>
                                <th>Title</th>
                                <th>Severity</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($reports->num_rows == 0): ?>
                            <tr><td colspan="8" style="text-align:center; padding:2rem; color:var(--text-secondary);">No reports match your filters.</td></tr>
                            <?php endif; ?>
                            <?php while($r = $reports->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $r['id']; ?></td>
                                <td><?php echo e($r['campaign_title']); ?></td>
                                <td><?php echo e($r['reporter']); ?></td>
                                <td><?php echo e($r['title']); ?></td>
                                <td><?php echo get_severity_badge($r['severity']); ?></td>
                                <td><?php echo get_status_badge($r['status']); ?></td>
                                <td><?php echo date('d M', strtotime($r['created_at'])); ?></td>
                                <td><a href="<?php echo BASE_URL; ?>/company/review_report.php?id=<?php echo $r['id']; ?>" class="btn btn-primary" style="padding:0.25rem 0.75rem; font-size:0.875rem;">Review</a></td>
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
