<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_role('user');

// Get current points
$stmt = $conn->prepare("SELECT points FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$current_points = $stmt->get_result()->fetch_assoc()['points'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reward_id'])) {
    if (verify_csrf_token($_POST['csrf_token'])) {
        $reward_id = (int)$_POST['reward_id'];
        
        $stmt = $conn->prepare("SELECT * FROM rewards WHERE id = ?");
        $stmt->bind_param("i", $reward_id);
        $stmt->execute();
        $reward = $stmt->get_result()->fetch_assoc();
        
        if (!$reward) {
            $error = 'Invalid reward.';
        } elseif ($reward['stock'] <= 0) {
            $error = 'Sorry, this reward is out of stock.';
        } elseif ($current_points < $reward['points_required']) {
            $error = 'Not enough points for this reward.';
        } else {
            $conn->begin_transaction();
            try {
                $cost = (int)$reward['points_required'];
                $uid = (int)$_SESSION['user_id'];
                // Deduct points only if balance is still sufficient (prevents double-spend race)
                $stmt_pts = $conn->prepare("UPDATE users SET points = points - ? WHERE id = ? AND points >= ?");
                $stmt_pts->bind_param("iii", $cost, $uid, $cost);
                $stmt_pts->execute();
                if ($stmt_pts->affected_rows !== 1) {
                    throw new Exception('Not enough points for this reward.');
                }
                // Decrease stock only if still available
                $stmt_stock = $conn->prepare("UPDATE rewards SET stock = stock - 1 WHERE id = ? AND stock > 0");
                $stmt_stock->bind_param("i", $reward_id);
                $stmt_stock->execute();
                if ($stmt_stock->affected_rows !== 1) {
                    throw new Exception('Sorry, this reward is out of stock.');
                }
                // Create redemption record
                $stmt = $conn->prepare("INSERT INTO redemptions (user_id, reward_id, points_spent) VALUES (?, ?, ?)");
                $stmt->bind_param("iii", $uid, $reward_id, $cost);
                $stmt->execute();
                
                $conn->commit();
                log_action('reward_redeemed', $_SESSION['user_id'], "Reward: {$reward['name']}");
                
                $current_points -= $cost;
                $_SESSION['points'] = $current_points;
                $success = 'Successfully redeemed! We will process your reward soon.';
            } catch (Exception $e) {
                $conn->rollback();
                $error = ($e instanceof mysqli_sql_exception) ? 'An error occurred during redemption.' : $e->getMessage();
            }
        }
    }
}

$rewards = $conn->query("SELECT * FROM rewards ORDER BY points_required ASC");
$history = $conn->query("
    SELECT r.*, rew.name, rew.category 
    FROM redemptions r 
    JOIN rewards rew ON r.reward_id = rew.id 
    WHERE r.user_id = {$_SESSION['user_id']} 
    ORDER BY r.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reward Store - BugBounty TH</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">🐛 BugBounty<span>TH</span></div>
        <div class="nav-user">
            <span class="points-badge">⚡ <?php echo number_format($current_points); ?> pts</span>
            <div class="avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 2)); ?></div>
            <?php echo e($_SESSION['username']); ?>
        </div>
    </nav>
    <div class="app-layout">
        <aside class="sidebar">
            <a href="<?php echo BASE_URL; ?>/user/dashboard.php">Dashboard</a>
            <a href="<?php echo BASE_URL; ?>/user/campaigns.php">Browse Campaigns</a>
            <a href="<?php echo BASE_URL; ?>/user/my_reports.php">My Reports</a>
            <a href="<?php echo BASE_URL; ?>/user/submit_report.php">Submit Report</a>
            <a href="<?php echo BASE_URL; ?>/user/redeem.php" class="active">Reward Store</a>
            <a href="<?php echo BASE_URL; ?>/public/logout.php" style="color:var(--danger); margin-top:2rem;">Logout</a>
        </aside>
        <main class="main-content">
            <div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h1>Reward Store</h1>
                    <p class="page-subtitle">Redeem your hard-earned points</p>
                </div>
                <div class="stat-card" style="padding:1rem; min-width:200px; text-align:center; margin:0;">
                    <div class="stat-label">Your Balance</div>
                    <div class="stat-value" style="color:var(--primary); font-size:1.5rem; margin:0;">⚡ <?php echo number_format($current_points); ?></div>
                </div>
            </div>
            
            <?php if($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>
            <?php if($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
            
            <div style="display:flex; gap:2rem; flex-wrap:wrap;">
                <div style="flex:2; min-width:300px;">
                    <div class="grid-3" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
                        <?php while($r = $rewards->fetch_assoc()): ?>
                        <div class="card" style="display:flex; flex-direction:column;">
                            <div style="margin-bottom:1rem;">
                                <span class="badge badge-blue"><?php echo e($r['category']); ?></span>
                                <?php if($r['stock'] <= 0): ?>
                                <span class="badge badge-red">Out of Stock</span>
                                <?php else: ?>
                                <span class="badge badge-gray"><?php echo $r['stock']; ?> left</span>
                                <?php endif; ?>
                            </div>
                            <h3 class="card-title"><?php echo e($r['name']); ?></h3>
                            <p class="card-desc" style="flex:1;"><?php echo e($r['description']); ?></p>
                            <div class="card-footer" style="margin-top:auto;">
                                <span style="font-weight:bold; color:var(--primary);">⚡ <?php echo number_format($r['points_required']); ?></span>
                                
                                <form method="post" onsubmit="return confirm('Redeem this reward for <?php echo $r['points_required']; ?> points?');">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="reward_id" value="<?php echo $r['id']; ?>">
                                    <button type="submit" class="btn btn-primary" <?php echo ($current_points < $r['points_required'] || $r['stock'] <= 0) ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : ''; ?>>
                                        Redeem
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                
                <div style="flex:1; min-width:300px;">
                    <div class="card">
                        <h3 class="card-title" style="margin-bottom:1rem;">Redemption History</h3>
                        <?php if($history->num_rows == 0): ?>
                            <p style="color:var(--text-secondary); text-align:center; padding:1rem;">No redemptions yet.</p>
                        <?php else: ?>
                            <div style="display:flex; flex-direction:column; gap:1rem;">
                            <?php while($h = $history->fetch_assoc()): ?>
                                <div style="border-bottom:1px solid var(--border); padding-bottom:1rem;">
                                    <div style="display:flex; justify-content:space-between; margin-bottom:0.25rem;">
                                        <strong><?php echo e($h['name']); ?></strong>
                                        <span class="badge badge-<?php echo $h['status']=='pending'?'yellow':($h['status']=='fulfilled'?'green':'red'); ?>"><?php echo e($h['status']); ?></span>
                                    </div>
                                    <div style="display:flex; justify-content:space-between; font-size:0.875rem; color:var(--text-secondary);">
                                        <span>-<?php echo number_format($h['points_spent']); ?> pts</span>
                                        <span><?php echo date('d M Y', strtotime($h['created_at'])); ?></span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
