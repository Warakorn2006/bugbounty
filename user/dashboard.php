<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('user');

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch stats using prepared statements
$uid = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT COUNT(*) FROM reports WHERE user_id = ?");
$stmt->bind_param("i", $uid); $stmt->execute();
$total_reports = $stmt->get_result()->fetch_row()[0]; $stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) FROM reports WHERE user_id = ? AND status IN ('Pending','Triaged')");
$stmt->bind_param("i", $uid); $stmt->execute();
$pending_reports = $stmt->get_result()->fetch_row()[0]; $stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) FROM reports WHERE user_id = ? AND status = 'Resolved'");
$stmt->bind_param("i", $uid); $stmt->execute();
$resolved_reports = $stmt->get_result()->fetch_row()[0]; $stmt->close();

// Fetch recent reports
$stmt = $conn->prepare("
    SELECT r.*, c.title as campaign_title, u.company_name 
    FROM reports r 
    JOIN campaigns c ON r.campaign_id = c.id 
    JOIN users u ON c.company_id = u.id 
    WHERE r.user_id = ? 
    ORDER BY r.created_at DESC LIMIT 5
");
$stmt->bind_param("i", $uid);
$stmt->execute();
$reports = $stmt->get_result();

// Fetch leaderboard (no user input - safe)
$leaderboard = $conn->query("SELECT username, points FROM users WHERE role='user' ORDER BY points DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">🐛 BugBounty<span>TH</span></div>
        <div class="nav-user">
            <span class="points-badge">⚡ <?php echo number_format($user['points']); ?> pts</span>
            <div class="avatar"><?php echo strtoupper(substr($user['username'], 0, 2)); ?></div>
            <?php echo e($user['username']); ?>
        </div>
    </nav>
    <div class="app-layout">
        <aside class="sidebar">
            <a href="<?php echo BASE_URL; ?>/user/dashboard.php" class="active">🏠 Dashboard</a>
            <a href="<?php echo BASE_URL; ?>/user/campaigns.php">🔍 Browse Campaigns</a>
            <a href="<?php echo BASE_URL; ?>/user/submit_report.php">📝 Submit Report</a>
            <a href="<?php echo BASE_URL; ?>/user/my_reports.php">📋 My Reports</a>
            <a href="<?php echo BASE_URL; ?>/user/redeem.php">🎁 Reward Store</a>
            <a href="<?php echo BASE_URL; ?>/public/logout.php" style="color:var(--danger); margin-top:2rem;">↩ Logout</a>
        </aside>
        <main class="main-content">
            <div class="page-header">
                <h1>Welcome back, <?php echo e($user['username']); ?>! 👋</h1>
                <p class="page-subtitle">Here's your bug hunting overview</p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Reports</div>
                    <div class="stat-value"><?php echo $total_reports; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Pending / Triaged</div>
                    <div class="stat-value"><?php echo $pending_reports; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Resolved</div>
                    <div class="stat-value" style="color:var(--success);"><?php echo $resolved_reports; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Points Earned</div>
                    <div class="stat-value" style="color:var(--primary);">⚡ <?php echo number_format($user['points']); ?></div>
                </div>
            </div>

            
            <div class="form-row">
                <div class="card" style="margin-bottom:0;">
                    <h3 class="card-title">Recent Reports</h3>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Campaign</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Points</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($reports->num_rows == 0): ?>
                                <tr><td colspan="4" style="text-align:center; color:var(--text-secondary);">No reports submitted yet.</td></tr>
                                <?php endif; ?>
                                <?php while($r = $reports->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo e($r['campaign_title']); ?></td>
                                    <td><?php echo get_severity_badge($r['severity']); ?> <?php echo e($r['vulnerability_type']); ?></td>
                                    <td><?php echo get_status_badge($r['status']); ?></td>
                                    <td><?php echo $r['points_awarded'] > 0 ? '+'.$r['points_awarded'] : '-'; ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="card" style="margin-bottom:0;">
                    <h3 class="card-title">Top Hunters</h3>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Researcher</th>
                                    <th>Points</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1; while($l = $leaderboard->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $rank++; ?></td>
                                    <td><div class="nav-user" style="display:inline-flex;">
                                        <div class="avatar" style="width:24px;height:24px;font-size:10px;"><?php echo strtoupper(substr($l['username'],0,2));?></div>
                                        <?php echo e($l['username']); ?>
                                    </div></td>
                                    <td style="font-weight:bold; color:var(--primary);"><?php echo number_format($l['points']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
