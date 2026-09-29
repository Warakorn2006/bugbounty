<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();

$res = $conn->query("SELECT * FROM rewards ORDER BY points_required ASC");
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
        <div class="nav-links">
            <a href="<?php echo BASE_URL; ?>/public/index.php">Home</a>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="/<?php echo $_SESSION['role']; ?>/dashboard.php" class="btn btn-primary">Dashboard</a>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>/public/login.php" class="btn btn-primary">Login</a>
            <?php endif; ?>
        </div>
    </nav>
    <div class="app-layout" style="display:block; padding: 2rem; max-width: 1200px; margin: 0 auto;">
        <div class="page-header">
            <h1>Reward Store</h1>
            <p class="page-subtitle">Redeem your hard-earned points for awesome rewards</p>
        </div>
        
        <div class="grid-3">
            <?php while($r = $res->fetch_assoc()): ?>
            <div class="card">
                <div style="margin-bottom:1rem;">
                    <span class="badge badge-blue"><?php echo e($r['category']); ?></span>
                    <?php if($r['stock'] <= 0): ?>
                    <span class="badge badge-red">Out of Stock</span>
                    <?php endif; ?>
                </div>
                <h3 class="card-title"><?php echo e($r['name']); ?></h3>
                <p class="card-desc"><?php echo e($r['description']); ?></p>
                <div class="card-footer">
                    <span class="stat-value" style="color:var(--primary); font-size:1.25rem;">
                        ⚡ <?php echo number_format($r['points_required']); ?> pts
                    </span>
                    <?php if(isset($_SESSION['user_id']) && $_SESSION['role'] === 'user'): ?>
                        <a href="<?php echo BASE_URL; ?>/user/redeem.php" class="btn btn-primary">Redeem</a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>/public/login.php" class="btn btn-outline">Login to Redeem</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html>
