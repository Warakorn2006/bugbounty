<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_role('admin');

$search = $_GET['search'] ?? '';

if (!empty($search)) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE role != 'admin' AND (username LIKE ? OR email LIKE ?) ORDER BY created_at DESC");
    $search_like = "%$search%";
    $stmt->bind_param("ss", $search_like, $search_like);
    $stmt->execute();
    $users = $stmt->get_result();
} else {
    $users = $conn->query("SELECT * FROM users WHERE role != 'admin' ORDER BY created_at DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - BugBounty TH</title>
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
            <a href="<?php echo BASE_URL; ?>/admin/users.php" class="active">All Users</a>
            <a href="<?php echo BASE_URL; ?>/admin/campaigns.php">All Campaigns</a>
            <a href="<?php echo BASE_URL; ?>/admin/disputes.php">Disputes</a>
            <a href="<?php echo BASE_URL; ?>/admin/logs.php">Security Logs</a>
            <a href="<?php echo BASE_URL; ?>/public/logout.php" style="color:var(--danger); margin-top:2rem;">Logout</a>
        </aside>
        <main class="main-content">
            <div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h1>Manage Users</h1>
                    <p class="page-subtitle">View and moderate platform users</p>
                </div>
                <form method="get" style="display:flex; gap:0.5rem;">
                    <input type="text" name="search" class="form-control" placeholder="Search users..." value="<?php echo e($search); ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>
            
            <?php if(isset($_SESSION['flash_msg'])): ?>
                <div class="alert alert-success"><?php echo e($_SESSION['flash_msg']); unset($_SESSION['flash_msg']); ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Points</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($u = $users->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $u['id']; ?></td>
                                <td><strong><?php echo e($u['username']); ?></strong><br><small style="color:var(--text-secondary);"><?php echo e($u['company_name']); ?></small></td>
                                <td><?php echo e($u['email']); ?></td>
                                <td><span class="badge badge-<?php echo $u['role']=='company'?'blue':'gray'; ?>"><?php echo e($u['role']); ?></span></td>
                                <td><?php echo number_format($u['points']); ?></td>
                                <td><span class="badge badge-<?php echo $u['status']=='active'?'green':'red'; ?>"><?php echo e($u['status']); ?></span></td>
                                <td>
                                    <form method="post" action="<?php echo BASE_URL; ?>/admin/ban_user.php" onsubmit="return confirm('Are you sure?');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <input type="hidden" name="action" value="<?php echo $u['status']=='active'?'ban':'unban'; ?>">
                                        <?php if($u['status'] == 'active'): ?>
                                            <button type="submit" class="btn btn-danger" style="padding:0.25rem 0.5rem; font-size:0.75rem;">Ban</button>
                                        <?php else: ?>
                                            <button type="submit" class="btn btn-success" style="padding:0.25rem 0.5rem; font-size:0.75rem;">Unban</button>
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
