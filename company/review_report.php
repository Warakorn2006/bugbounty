<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_role('company');

if (!isset($_GET['id'])) { header("Location: " . BASE_URL . "/company/reports.php"); exit; }
$report_id = (int)$_GET['id'];
$company_id = $_SESSION['user_id'];

// Get report details
$stmt = $conn->prepare("
    SELECT r.*, c.title as campaign_title, c.reward_sqli, c.reward_xss, c.reward_other, u.username as reporter, u.id as reporter_id
    FROM reports r 
    JOIN campaigns c ON r.campaign_id = c.id 
    JOIN users u ON r.user_id = u.id 
    WHERE r.id = ? AND c.company_id = ?
");
$stmt->bind_param("ii", $report_id, $company_id);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();

if (!$report) { die("Report not found or unauthorized."); }

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (verify_csrf_token($_POST['csrf_token'])) {
        $action = $_POST['action'];
        $comment = trim($_POST['comment']);
        
        if ($action === 'approve') {
            // Prevent duplicate reward: only allow approve if still Pending/Triaged
            if ($report['status'] !== 'Pending' && $report['status'] !== 'Triaged') {
                $error = "This report has already been processed (status: {$report['status']}).";
            } else {
                $points = (int)$_POST['points'];
                if ($points <= 0) $error = "Points must be > 0";
                else {
                    $conn->begin_transaction();
                    try {
                        // Update report (with status guard in WHERE to prevent race condition)
                        $stmt_upd = $conn->prepare("UPDATE reports SET status = 'Resolved', points_awarded = ?, company_comment = ? WHERE id = ? AND status IN ('Pending', 'Triaged')");
                        $stmt_upd->bind_param("isi", $points, $comment, $report_id);
                        $stmt_upd->execute();
                        
                        if ($stmt_upd->affected_rows === 0) {
                            throw new Exception("Report already processed (concurrent request).");
                        }
                        
                        // Award points
                        $stmt_pts = $conn->prepare("UPDATE users SET points = points + ? WHERE id = ?");
                        $stmt_pts->bind_param("ii", $points, $report['reporter_id']);
                        $stmt_pts->execute();
                        
                        $conn->commit();
                        log_action('report_resolved', $company_id, "Report ID: $report_id, Points: $points");
                        $success = "Report approved and $points points awarded!";
                        $report['status'] = 'Resolved';
                    } catch (Exception $e) {
                        $conn->rollback();
                        $error = "System error occurred: " . e($e->getMessage());
                    }
                }
            }
        } elseif ($action === 'reject' || $action === 'duplicate') {
            $status = $action === 'reject' ? 'Rejected' : 'Duplicate';
            if (empty($comment)) {
                $error = "Please provide a reason for rejecting/marking duplicate.";
            } else {
                $stmt = $conn->prepare("UPDATE reports SET status = ?, company_comment = ? WHERE id = ?");
                $stmt->bind_param("ssi", $status, $comment, $report_id);
                $stmt->execute();
                log_action("report_$action", $company_id, "Report ID: $report_id");
                $success = "Report marked as $status.";
                $report['status'] = $status;
            }
        }
    }
}

// Calculate suggested max points
$max_points = $report['reward_other'];
if ($report['vulnerability_type'] === 'SQL Injection') $max_points = $report['reward_sqli'];
if ($report['vulnerability_type'] === 'XSS') $max_points = $report['reward_xss'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Review Report - BugBounty TH</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">🐛 BugBounty<span>TH</span> <small style="color:var(--text-secondary);font-weight:normal;">| Company</small></div>
        <div class="nav-user">
            <div class="avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 2)); ?></div>
            <?php echo e($_SESSION['username']); ?>
        </div>
    </nav>
    <div class="app-layout">
        <aside class="sidebar">
            <a href="<?php echo BASE_URL; ?>/company/dashboard.php">Dashboard</a>
            <a href="<?php echo BASE_URL; ?>/company/my_campaigns.php">My Campaigns</a>
            <a href="<?php echo BASE_URL; ?>/company/create_campaign.php">Create Campaign</a>
            <a href="<?php echo BASE_URL; ?>/company/reports.php" class="active">Reports Inbox</a>
            <a href="<?php echo BASE_URL; ?>/public/logout.php" style="color:var(--danger); margin-top:2rem;">Logout</a>
        </aside>
        <main class="main-content">
            <a href="<?php echo BASE_URL; ?>/company/reports.php" style="color:var(--text-secondary); margin-bottom:1rem; display:inline-block;">← Back to Inbox</a>
            
            <?php if($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>
            <?php if($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
            
            <div style="display:flex; gap:2rem;">
                <!-- Left: Report Details -->
                <div style="flex:2;">
                    <div class="card">
                        <div style="display:flex; justify-content:space-between; margin-bottom:1.5rem; border-bottom:1px solid var(--border); padding-bottom:1rem;">
                            <div>
                                <h1 style="font-size:1.5rem; margin-bottom:0.5rem;"><?php echo e($report['title']); ?></h1>
                                <div style="color:var(--text-secondary);">
                                    By <strong><?php echo e($report['reporter']); ?></strong> in <strong><?php echo e($report['campaign_title']); ?></strong>
                                </div>
                            </div>
                            <div style="text-align:right;">
                                <div><?php echo get_status_badge($report['status']); ?></div>
                                <div style="margin-top:0.5rem;"><?php echo date('d M Y, H:i', strtotime($report['created_at'])); ?></div>
                            </div>
                        </div>
                        
                        <div style="display:flex; gap:1rem; margin-bottom:1.5rem;">
                            <div style="background:#f9fafb; padding:1rem; border-radius:8px; flex:1;">
                                <div style="font-size:0.875rem; color:var(--text-secondary);">Type</div>
                                <div style="font-weight:600;"><?php echo e($report['vulnerability_type']); ?></div>
                            </div>
                            <div style="background:#f9fafb; padding:1rem; border-radius:8px; flex:1;">
                                <div style="font-size:0.875rem; color:var(--text-secondary);">Severity</div>
                                <div><?php echo get_severity_badge($report['severity']); ?></div>
                            </div>
                        </div>
                        
                        <h3 style="margin-bottom:0.5rem;">Description</h3>
                        <div style="background:#f9fafb; padding:1rem; border-radius:8px; margin-bottom:1.5rem; white-space:pre-wrap; border:1px solid var(--border);"><?php echo e($report['description']); ?></div>
                        
                        <h3 style="margin-bottom:0.5rem;">Steps to Reproduce</h3>
                        <div style="background:#f9fafb; padding:1rem; border-radius:8px; margin-bottom:1.5rem; white-space:pre-wrap; border:1px solid var(--border); font-family:monospace;"><?php echo e($report['steps_to_reproduce']); ?></div>
                        
                        <?php if(!empty($report['impact'])): ?>
                        <h3 style="margin-bottom:0.5rem;">Impact</h3>
                        <div style="background:#f9fafb; padding:1rem; border-radius:8px; margin-bottom:1.5rem; white-space:pre-wrap; border:1px solid var(--border);"><?php echo e($report['impact']); ?></div>
                        <?php endif; ?>
                        
                        <?php if(!empty($report['poc_file'])): ?>
                        <h3 style="margin-bottom:0.5rem;">Proof of Concept (PoC)</h3>
                        <a href="<?php echo UPLOAD_WEB_PATH . $report['poc_file']; ?>" target="_blank" class="btn btn-outline" style="display:inline-block;">📎 Download / View Attachment</a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Right: Action Panel -->
                <div style="flex:1; min-width:300px;">
                    <div class="card" style="position:sticky; top:2rem;">
                        <h3 style="margin-bottom:1rem; border-bottom:1px solid var(--border); padding-bottom:0.5rem;">Review Actions</h3>
                        
                        <?php if($report['status'] === 'Pending' || $report['status'] === 'Triaged'): ?>
                        
                        <div style="background:#eff6ff; padding:1rem; border-radius:8px; margin-bottom:1.5rem;">
                            <div style="font-size:0.875rem; color:var(--text-secondary); margin-bottom:0.5rem;">Suggested Max Reward for this Type:</div>
                            <div style="font-size:1.5rem; font-weight:bold; color:var(--primary);">⚡ <?php echo $max_points; ?> pts</div>
                        </div>
                        
                        <form method="post" style="margin-bottom:1.5rem; border-bottom:1px solid var(--border); padding-bottom:1.5rem;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="approve">
                            <div class="form-group">
                                <label>Points to Award</label>
                                <input type="number" name="points" class="form-control" value="<?php echo $max_points; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Feedback (Optional)</label>
                                <textarea name="comment" class="form-control" placeholder="Great finding!"></textarea>
                            </div>
                            <button type="submit" class="btn btn-success" style="width:100%;" onclick="return confirm('Award points and resolve?');">Approve & Resolve</button>
                        </form>
                        
                        <form method="post">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="reject">
                            <div class="form-group">
                                <label>Reason for Rejection / Duplicate</label>
                                <textarea name="comment" class="form-control" required placeholder="Provide reason..."></textarea>
                            </div>
                            <div style="display:flex; gap:0.5rem;">
                                <button type="submit" class="btn btn-outline" style="flex:1;" onclick="this.form.action.value='duplicate';">Mark Duplicate</button>
                                <button type="submit" class="btn btn-danger" style="flex:1;">Reject</button>
                            </div>
                        </form>
                        
                        <?php else: ?>
                        <div class="alert alert-<?php echo $report['status']=='Resolved'?'success':'danger'; ?>">
                            This report is closed (<?php echo $report['status']; ?>).
                        </div>
                        <?php if(!empty($report['company_comment'])): ?>
                            <div style="margin-top:1rem;">
                                <label>Your Comment:</label>
                                <div style="background:#f3f4f6; padding:1rem; border-radius:6px; margin-top:0.5rem;"><?php echo e($report['company_comment']); ?></div>
                            </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
