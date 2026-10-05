<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Fetch stats
$stats = [
    'campaigns' => $conn->query("SELECT COUNT(*) FROM campaigns WHERE status='active'")->fetch_row()[0],
    'researchers' => $conn->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetch_row()[0],
    'points' => $conn->query("SELECT SUM(points_awarded) FROM reports WHERE status='Resolved'")->fetch_row()[0] ?? 0
];

// Fetch active campaigns
$campaigns = $conn->query("SELECT c.*, u.company_name FROM campaigns c JOIN users u ON c.company_id = u.id WHERE c.status='active' ORDER BY c.created_at DESC LIMIT 3");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BugBounty TH - Find Bugs, Earn Rewards</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        .hero { text-align: center; padding: 4rem 2rem; background: white; border-bottom: 1px solid var(--border); }
        .hero h1 { font-size: 3rem; margin-bottom: 1rem; color: var(--text-primary); }
        .hero p { font-size: 1.25rem; color: var(--text-secondary); margin-bottom: 2rem; max-width: 600px; margin-left: auto; margin-right: auto; }
        .hero .btn { font-size: 1.125rem; padding: 0.75rem 1.5rem; margin: 0 0.5rem; }
        .section { padding: 4rem 2rem; max-width: 1200px; margin: 0 auto; }
        .section-title { text-align: center; margin-bottom: 3rem; font-size: 2rem; }
        .steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; text-align: center; }
        .step-icon { font-size: 3rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">🐛 BugBounty<span>TH</span></div>
        <div class="nav-links">
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="<?php echo BASE_URL; ?>/<?php echo e($_SESSION['role']); ?>/dashboard.php" class="btn btn-primary">Dashboard</a>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>/public/login.php">Login</a>
                <a href="<?php echo BASE_URL; ?>/public/register.php" class="btn btn-primary">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="hero">
        <h1>Find Bugs, Earn Rewards</h1>
        <p>Join the leading bug bounty platform in Thailand. Help companies secure their systems and get rewarded for your findings.</p>
        <div>
            <a href="<?php echo BASE_URL; ?>/public/register.php" class="btn btn-primary">Start Hunting</a>
            <a href="<?php echo BASE_URL; ?>/public/register.php?role=company" class="btn btn-outline">Post a Campaign</a>
        </div>
    </div>

    <div class="section">
        <div class="stats-grid" style="text-align: center;">
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['campaigns']); ?>+</div>
                <div class="stat-label">Active Campaigns</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['researchers']); ?>+</div>
                <div class="stat-label">Researchers</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['points']); ?></div>
                <div class="stat-label">Points Awarded</div>
            </div>
        </div>
    </div>

    <div class="section" style="background: white;">
        <h2 class="section-title">How It Works</h2>
        <div class="steps">
            <div>
                <div class="step-icon">📝</div>
                <h3>1. Register</h3>
                <p>Create an account as a security researcher.</p>
            </div>
            <div>
                <div class="step-icon">🔍</div>
                <h3>2. Find Campaign</h3>
                <p>Browse active campaigns and start testing in-scope targets.</p>
            </div>
            <div>
                <div class="step-icon">💰</div>
                <h3>3. Submit & Earn</h3>
                <p>Report vulnerabilities, get them verified, and earn points.</p>
            </div>
        </div>
    </div>

    <div class="section">
        <h2 class="section-title">Featured Campaigns</h2>
        <div class="grid-3">
            <?php while($c = $campaigns->fetch_assoc()): ?>
            <div class="card campaign-card">
                <div class="card-header">
                    <div class="company-logo"><?php echo substr($c['company_name'], 0, 2); ?></div>
                    <div>
                        <h3 class="card-title"><?php echo e($c['title']); ?></h3>
                        <p class="card-subtitle"><?php echo e($c['company_name']); ?></p>
                    </div>
                </div>
                <p class="card-desc"><?php echo e(substr($c['description'], 0, 100)); ?>...</p>
                <div class="card-footer">
                    <span class="meta">Max Reward: <?php echo format_points($c['reward_sqli']); ?></span>
                    <a href="<?php echo BASE_URL; ?>/public/login.php" class="btn btn-outline">View Details</a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <footer style="text-align: center; padding: 2rem; color: var(--text-secondary); border-top: 1px solid var(--border);">
        &copy; <?php echo date('Y'); ?> BugBounty TH. All rights reserved.
    </footer>
</body>
</html>
