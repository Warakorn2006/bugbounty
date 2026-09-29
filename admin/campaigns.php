<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['campaign_id'])) {
    if (verify_csrf_token($_POST['csrf_token'])) {
        $cid = (int)$_POST['campaign_id'];
        $action = $_POST['action']; // 'approve', 'reject', 'close'
        
        $new_status = '';
        if ($action === 'approve') $new_status = 'active';
        if ($action === 'reject') $new_status = 'closed'; // or rejected but schema has closed
        if ($action === 'close') $new_status = 'closed';
        
        if ($new_status) {
            $stmt = $conn->prepare("UPDATE campaigns SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $cid);
            $stmt->execute();
            log_action("campaign_$action", $_SESSION['user_id'], "Campaign ID: $cid");
            $_SESSION['flash_msg'] = "Campaign status updated.";
        }
    }
    header("Location: " . BASE_URL . "/admin/campaigns.php");
    exit;
}

$campaigns = $conn->query("
    SELECT c.*, u.company_name, u.username 
    FROM campaigns c 
    JOIN users u ON c.company_id = u.id 
    ORDER BY CASE WHEN c.status = 'pending' THEN 1 ELSE 2 END, c.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Campaigns - BugBounty TH</title>
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
            <a href="<?php echo BASE_URL; ?>/admin/campaigns.php" class="active">All Campaigns</a>
            <a href="<?php echo BASE_URL; ?>/admin/disputes.php">Disputes</a>
            <a href="<?php echo BASE_URL; ?>/admin/logs.php">Security Logs</a>
            <a href="<?php echo BASE_URL; ?>/public/logout.php" style="color:var(--danger); margin-top:2rem;">Logout</a>
        </aside>
        <main class="main-content">
            <div class="page-header">
                <h1>Manage Campaigns</h1>
                <p class="page-subtitle">Approve or close bug bounty programs</p>
            </div>
            
            <?php if(isset($_SESSION['flash_msg'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['flash_msg']; unset($_SESSION['flash_msg']); ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Company</th>
                                <th>Title</th>
                                <th>Target</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($c = $campaigns->fetch_assoc()): ?>
                            <tr style="<?php echo $c['status']=='pending' ? 'background:#fefce8;' : ''; ?>">
                                <td><?php echo $c['id']; ?></td>
                                <td><?php echo e($c['company_name']); ?><br><small style="color:var(--text-secondary);"><?php echo e($c['username']); ?></small></td>
                                <td><strong><?php echo e($c['title']); ?></strong></td>
                                <td><a href="<?php echo e($c['target_url']); ?>" target="_blank"><?php echo e($c['target_url']); ?></a></td>
                                <td><?php echo get_status_badge($c['status']); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($c['created_at'])); ?></td>
                                <td>
                                    <form method="post" style="display:flex; gap:0.25rem;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="campaign_id" value="<?php echo $c['id']; ?>">
                                        
                                        <?php if($c['status'] == 'pending'): ?>
                                            <button type="submit" name="action" value="approve" class="btn btn-success" style="padding:0.25rem 0.5rem; font-size:0.75rem;">Approve</button>
                                            <button type="submit" name="action" value="reject" class="btn btn-danger" style="padding:0.25rem 0.5rem; font-size:0.75rem;">Reject</button>
                                        <?php elseif($c['status'] == 'active'): ?>
                                            <button type="submit" name="action" value="close" class="btn btn-outline" style="padding:0.25rem 0.5rem; font-size:0.75rem;" onclick="return confirm('Force close this campaign?');">Force Close</button>
                                        <?php endif; ?>
                                    </form>
                                </td>
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
