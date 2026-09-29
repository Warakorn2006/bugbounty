<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$logs = $conn->query("
    SELECT s.*, u.username, u.role 
    FROM security_logs s 
    LEFT JOIN users u ON s.user_id = u.id 
    ORDER BY s.created_at DESC 
    LIMIT 100
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Security Logs - BugBounty TH</title>
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
            <a href="<?php echo BASE_URL; ?>/admin/dashboard.php">Dashboard</a>
            <a href="<?php echo BASE_URL; ?>/admin/users.php">All Users</a>
            <a href="<?php echo BASE_URL; ?>/admin/campaigns.php">All Campaigns</a>
            <a href="<?php echo BASE_URL; ?>/admin/disputes.php">Disputes</a>
            <a href="<?php echo BASE_URL; ?>/admin/logs.php" class="active">Security Logs</a>
            <a href="<?php echo BASE_URL; ?>/public/logout.php" style="color:var(--danger); margin-top:2rem;">Logout</a>
        </aside>
        <main class="main-content">
            <div class="page-header">
                <h1>Security Audit Logs</h1>
                <p class="page-subtitle">Recent system activity (Last 100 events)</p>
            </div>
            
            <div class="card">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Action Type</th>
                                <th>User</th>
                                <th>IP Address</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody style="font-size:0.875rem;">
                            <?php while($l = $logs->fetch_assoc()): 
                                $color = '';
                                if(strpos($l['action_type'], 'failed') !== false) $color = 'color:var(--danger);';
                                if(strpos($l['action_type'], 'success') !== false) $color = 'color:var(--success);';
                                if(strpos($l['action_type'], 'ban') !== false) $color = 'color:var(--warning);';
                            ?>
                            <tr>
                                <td style="color:var(--text-secondary);"><?php echo date('Y-m-d H:i:s', strtotime($l['created_at'])); ?></td>
                                <td style="font-weight:bold; <?php echo $color; ?>"><?php echo e($l['action_type']); ?></td>
                                <td>
                                    <?php if($l['username']): ?>
                                        <?php echo e($l['username']); ?> <span class="badge badge-gray"><?php echo e($l['role']); ?></span>
                                    <?php else: ?>
                                        <span style="color:var(--text-secondary);">System/Guest</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-family:monospace; color:var(--text-secondary);"><?php echo e($l['ip_address']); ?></td>
                                <td><?php echo e($l['details']); ?></td>
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
