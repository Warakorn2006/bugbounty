<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_role('user');

// Handle Dispute
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dispute_report_id'])) {
    if (verify_csrf_token($_POST['csrf_token'])) {
        $report_id = (int)$_POST['dispute_report_id'];
        $reason = trim($_POST['dispute_reason'] ?? '');
        
        // Verify report belongs to user, is rejected, and has no existing dispute
        $stmt = $conn->prepare("SELECT r.id FROM reports r WHERE r.id = ? AND r.user_id = ? AND r.status = 'Rejected' AND NOT EXISTS (SELECT 1 FROM disputes d WHERE d.report_id = r.id)");
        $stmt->bind_param("ii", $report_id, $_SESSION['user_id']);
        $stmt->execute();
        if ($reason === '') {
            $_SESSION['flash_msg'] = "Please provide a reason for the dispute.";
        } elseif ($stmt->get_result()->num_rows > 0) {
            $stmt2 = $conn->prepare("INSERT INTO disputes (report_id, user_id, reason) VALUES (?, ?, ?)");
            $stmt2->bind_param("iis", $report_id, $_SESSION['user_id'], $reason);
            $stmt2->execute();
            $_SESSION['flash_msg'] = "Dispute opened successfully.";
            log_action('dispute_opened', $_SESSION['user_id'], "Report ID: $report_id");
        } else {
            $_SESSION['flash_msg'] = "This report cannot be disputed (not rejected or already disputed).";
        }
    }
    header("Location: " . BASE_URL . "/user/my_reports.php");
    exit;
}

$reports = $conn->query("
    SELECT r.*, c.title as campaign_title, u.company_name 
    FROM reports r 
    JOIN campaigns c ON r.campaign_id = c.id 
    JOIN users u ON c.company_id = u.id 
    WHERE r.user_id = {$_SESSION['user_id']} 
    ORDER BY r.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Reports - BugBounty TH</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 50; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 2rem; border-radius: 10px; width: 100%; max-width: 500px; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">🐛 BugBounty<span>TH</span></div>
        <div class="nav-user">
            <span class="points-badge">⚡ <?php echo number_format($_SESSION['points'] ?? 0); ?> pts</span>
            <div class="avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 2)); ?></div>
            <?php echo e($_SESSION['username']); ?>
        </div>
    </nav>
    <div class="app-layout">
        <aside class="sidebar">
            <a href="<?php echo BASE_URL; ?>/user/dashboard.php">Dashboard</a>
            <a href="<?php echo BASE_URL; ?>/user/campaigns.php">Browse Campaigns</a>
            <a href="<?php echo BASE_URL; ?>/user/my_reports.php" class="active">My Reports</a>
            <a href="<?php echo BASE_URL; ?>/user/submit_report.php">Submit Report</a>
            <a href="<?php echo BASE_URL; ?>/user/redeem.php">Reward Store</a>
            <a href="<?php echo BASE_URL; ?>/public/logout.php" style="color:var(--danger); margin-top:2rem;">Logout</a>
        </aside>
        <main class="main-content">
            <div class="page-header">
                <h1>My Submitted Reports</h1>
                <p class="page-subtitle">Track the status of your vulnerabilities</p>
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
                                <th>Campaign</th>
                                <th>Title</th>
                                <th>Severity</th>
                                <th>Status</th>
                                <th>Points</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($r = $reports->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $r['id']; ?></td>
                                <td><?php echo e($r['campaign_title']); ?><br><small style="color:var(--text-secondary);"><?php echo e($r['company_name']); ?></small></td>
                                <td><?php echo e($r['title']); ?><br><small style="color:var(--text-secondary);"><?php echo e($r['vulnerability_type']); ?></small></td>
                                <td><?php echo get_severity_badge($r['severity']); ?></td>
                                <td><?php echo get_status_badge($r['status']); ?></td>
                                <td style="font-weight:bold; color:var(--success);"><?php echo $r['points_awarded'] > 0 ? '+'.$r['points_awarded'] : '-'; ?></td>
                                <td><?php echo date('d M Y', strtotime($r['created_at'])); ?></td>
                                <td>
                                    <?php if($r['status'] === 'Rejected'): ?>
                                        <?php 
                                        $d = $conn->query("SELECT id FROM disputes WHERE report_id={$r['id']}")->num_rows;
                                        if($d == 0):
                                        ?>
                                        <button class="btn btn-outline" style="padding:0.25rem 0.5rem; font-size:0.75rem;" onclick="openDispute(<?php echo $r['id']; ?>)">Dispute</button>
                                        <?php else: ?>
                                        <span class="badge badge-yellow">Disputed</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php if(!empty($r['company_comment'])): ?>
                                        <button class="btn btn-outline" style="padding:0.25rem 0.5rem; font-size:0.75rem;" onclick="alert(<?php echo e(json_encode($r['company_comment'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE)); ?>)">Feedback</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if($reports->num_rows == 0): ?>
                            <tr><td colspan="8" style="text-align:center; padding:2rem;">No reports submitted yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <div id="disputeModal" class="modal">
        <div class="modal-content">
            <h3 style="margin-bottom:1rem;">Open Dispute</h3>
            <p style="margin-bottom:1rem; color:var(--text-secondary);">If you believe your report was unfairly rejected, provide a reason for the admins to review.</p>
            <form method="post">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="dispute_report_id" id="dispute_report_id">
                <div class="form-group">
                    <label>Reason for Dispute</label>
                    <textarea name="dispute_reason" class="form-control" required></textarea>
                </div>
                <div style="display:flex; gap:1rem; justify-content:flex-end;">
                    <button type="button" class="btn btn-outline" onclick="closeDispute()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Dispute</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openDispute(id) {
            document.getElementById('dispute_report_id').value = id;
            document.getElementById('disputeModal').classList.add('active');
        }
        function closeDispute() {
            document.getElementById('disputeModal').classList.remove('active');
        }
    </script>
</body>
</html>
