<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

// Stats
$stats = [
    'users' => $conn->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetch_row()[0],
    'companies' => $conn->query("SELECT COUNT(*) FROM users WHERE role='company'")->fetch_row()[0],
    'active_campaigns' => $conn->query("SELECT COUNT(*) FROM campaigns WHERE status='active'")->fetch_row()[0],
    'open_disputes' => $conn->query("SELECT COUNT(*) FROM disputes WHERE status='open'")->fetch_row()[0],
];

// Recent Logs
$logs = $conn->query("SELECT s.*, u.username FROM security_logs s LEFT JOIN users u ON s.user_id = u.id ORDER BY s.created_at DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - BugBounty TH</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <nav class="navbar" style="background:#111827; color:white;">
        <div class="nav-brand">🛡️ BugBounty<span>TH</span> <small style="color:#9ca3af;font-weight:normal;">| System Admin</small></div>
        <div class="nav-user">
            <div class="avatar" style="background:#4b5563;"><?php echo strtoupper(substr($_SESSION['username'], 0, 2)); ?></div>
            <?php echo e($_SESSION['username']); ?>
        </div>
    </nav>
    <div class="app-layout">
        <aside class="sidebar">
            <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="active">Dashboard</a>
            <a href="<?php echo BASE_URL; ?>/admin/users.php">All Users</a>
            <a href="<?php echo BASE_URL; ?>/admin/campaigns.php">All Campaigns</a>
            <a href="<?php echo BASE_URL; ?>/admin/disputes.php">Disputes</a>
            <a href="<?php echo BASE_URL; ?>/admin/logs.php">Security Logs</a>
            <a href="<?php echo BASE_URL; ?>/public/logout.php" style="color:var(--danger); margin-top:2rem;">Logout</a>
        </aside>
        <main class="main-content">
            <div class="page-header">
                <h1>Admin Dashboard</h1>
                <p class="page-subtitle">Platform overview and health</p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Users</div>
                    <div class="stat-value"><?php echo $stats['users']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Companies</div>
                    <div class="stat-value"><?php echo $stats['companies']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Active Campaigns</div>
                    <div class="stat-value"><?php echo $stats['active_campaigns']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Open Disputes</div>
                    <div class="stat-value" style="color:var(--danger);"><?php echo $stats['open_disputes']; ?></div>
                </div>
            </div>
            
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                    <h3 class="card-title" style="margin:0;">Recent Security Logs</h3>
                    <a href="<?php echo BASE_URL; ?>/admin/logs.php" class="btn btn-outline">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Action</th>
                                <th>User</th>
                                <th>IP Address</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($l = $logs->fetch_assoc()): ?>
                            <tr>
                                <td style="font-size:0.875rem; color:var(--text-secondary);"><?php echo date('Y-m-d H:i:s', strtotime($l['created_at'])); ?></td>
                                <td><?php echo e($l['action_type']); ?></td>
                                <td><?php echo $l['username'] ? e($l['username']) : '-'; ?></td>
                                <td style="font-family:monospace; font-size:0.875rem;"><?php echo e($l['ip_address']); ?></td>
                                <td style="font-size:0.875rem;"><?php echo e($l['details']); ?></td>
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
