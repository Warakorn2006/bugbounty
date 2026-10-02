<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dispute_id'])) {
    if (verify_csrf_token($_POST['csrf_token'])) {
        $did = (int)$_POST['dispute_id'];
        $note = trim($_POST['admin_note']);
        $action = $_POST['action']; // resolve_reject, resolve_approve
        
        $conn->begin_transaction();
        try {
            // Get dispute info (including status to prevent double-processing)
            $stmt = $conn->prepare("SELECT report_id, user_id, status FROM disputes WHERE id = ?");
            $stmt->bind_param("i", $did);
            $stmt->execute();
            $dis = $stmt->get_result()->fetch_assoc();
            
            // Prevent duplicate resolution
            if (!$dis || $dis['status'] !== 'open') {
                throw new Exception("Dispute not found or already resolved.");
            }
            
            if ($action === 'resolve_approve') {
                $points = (int)$_POST['award_points'];
                // Update report with prepared statement + status guard
                $stmt_rpt = $conn->prepare("UPDATE reports SET status = 'Resolved', points_awarded = ? WHERE id = ? AND status NOT IN ('Resolved')");
                $stmt_rpt->bind_param("ii", $points, $dis['report_id']);
                $stmt_rpt->execute();
                
                if ($stmt_rpt->affected_rows > 0) {
                    // Only give points if report was actually updated (not already resolved)
                    $stmt_pts = $conn->prepare("UPDATE users SET points = points + ? WHERE id = ?");
                    $stmt_pts->bind_param("ii", $points, $dis['user_id']);
                    $stmt_pts->execute();
                }
                
                // Close dispute
                $stmt2 = $conn->prepare("UPDATE disputes SET status = 'resolved', admin_note = ? WHERE id = ? AND status = 'open'");
                $stmt2->bind_param("si", $note, $did);
                $stmt2->execute();
                
                log_action('dispute_resolved_approve', $_SESSION['user_id'], "Dispute ID: $did");
                $_SESSION['flash_msg'] = "Dispute resolved in favor of user.";
            } else {
                // Close dispute
                $stmt2 = $conn->prepare("UPDATE disputes SET status = 'resolved', admin_note = ? WHERE id = ? AND status = 'open'");
                $stmt2->bind_param("si", $note, $did);
                $stmt2->execute();
                log_action('dispute_resolved_reject', $_SESSION['user_id'], "Dispute ID: $did");
                $_SESSION['flash_msg'] = "Dispute resolved in favor of company.";
            }
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['flash_msg'] = "Error: " . $e->getMessage();
        }
    }
    header("Location: " . BASE_URL . "/admin/disputes.php");
    exit;
}

$disputes = $conn->query("
    SELECT d.*, r.title as report_title, r.vulnerability_type, r.severity, u.username as reporter, c.title as campaign_title
    FROM disputes d
    JOIN reports r ON d.report_id = r.id
    JOIN users u ON d.user_id = u.id
    JOIN campaigns c ON r.campaign_id = c.id
    ORDER BY CASE WHEN d.status = 'open' THEN 1 ELSE 2 END, d.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Disputes - BugBounty TH</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        details { background:#f9fafb; border:1px solid var(--border); border-radius:6px; margin-bottom:1rem; }
        summary { padding:1rem; cursor:pointer; font-weight:500; }
        .details-content { padding:1rem; border-top:1px solid var(--border); background:white; }
    </style>
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
            <a href="<?php echo BASE_URL; ?>/admin/disputes.php" class="active">Disputes</a>
            <a href="<?php echo BASE_URL; ?>/admin/logs.php">Security Logs</a>
            <a href="<?php echo BASE_URL; ?>/public/logout.php" style="color:var(--danger); margin-top:2rem;">Logout</a>
        </aside>
        <main class="main-content">
            <div class="page-header">
                <h1>Dispute Resolution</h1>
                <p class="page-subtitle">Mediate conflicts between researchers and companies</p>
            </div>
            
            <?php if(isset($_SESSION['flash_msg'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['flash_msg']; unset($_SESSION['flash_msg']); ?></div>
            <?php endif; ?>
            
            <?php if($disputes->num_rows == 0): ?>
                <div class="card" style="text-align:center; padding:3rem; color:var(--text-secondary);">No disputes found.</div>
            <?php endif; ?>
            
            <?php while($d = $disputes->fetch_assoc()): ?>
            <details>
                <summary style="display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <span class="badge badge-<?php echo $d['status']=='open'?'red':'gray'; ?>"><?php echo strtoupper($d['status']); ?></span>
                        <strong style="margin-left:0.5rem;">Report #<?php echo $d['report_id']; ?>: <?php echo e($d['report_title']); ?></strong>
                    </div>
                    <div style="color:var(--text-secondary); font-size:0.875rem;">By <?php echo e($d['reporter']); ?> • <?php echo date('M d, Y', strtotime($d['created_at'])); ?></div>
                </summary>
                <div class="details-content">
                    <div class="form-row">
                        <div>
                            <h4>Dispute Reason (from Researcher)</h4>
                            <p style="background:#fef2f2; padding:1rem; border-radius:6px; margin:0.5rem 0 1rem;"><?php echo e($d['reason']); ?></p>
                        </div>
                        <div>
                            <h4>Report Details</h4>
                            <ul style="list-style:none; padding:1rem; background:#f3f4f6; border-radius:6px; margin:0.5rem 0 1rem; font-size:0.875rem;">
                                <li><strong>Campaign:</strong> <?php echo e($d['campaign_title']); ?></li>
                                <li><strong>Type:</strong> <?php echo e($d['vulnerability_type']); ?></li>
                                <li><strong>Severity:</strong> <?php echo get_severity_badge($d['severity']); ?></li>
                            </ul>
                        </div>
                    </div>
                    
                    <?php if($d['status'] == 'open'): ?>
                    <form method="post" style="border-top:1px solid var(--border); padding-top:1rem; margin-top:1rem;">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="dispute_id" value="<?php echo $d['id']; ?>">
                        
                        <div class="form-group">
                            <label>Admin Resolution Note (Visible to both parties)</label>
                            <textarea name="admin_note" class="form-control" required placeholder="Explain the decision..."></textarea>
                        </div>
                        
                        <div style="display:flex; gap:1rem; align-items:flex-end;">
                            <div style="flex:1;">
                                <label>Award Points (If approving in favor of researcher)</label>
                                <input type="number" name="award_points" class="form-control" value="0">
                            </div>
                            <button type="submit" name="action" value="resolve_approve" class="btn btn-success" onclick="return confirm('Override company and award points?');">Force Approve Report</button>
                            <button type="submit" name="action" value="resolve_reject" class="btn btn-outline" onclick="return confirm('Keep report rejected?');">Keep Rejected (Side with Company)</button>
                        </div>
                    </form>
                    <?php else: ?>
                    <div style="border-top:1px solid var(--border); padding-top:1rem; margin-top:1rem;">
                        <h4>Admin Resolution Note</h4>
                        <p style="background:#f0fdf4; padding:1rem; border-radius:6px; margin:0.5rem 0 0;"><?php echo e($d['admin_note']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </details>
            <?php endwhile; ?>
        </main>
    </div>
</body>
</html>
