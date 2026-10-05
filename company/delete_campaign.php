<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_role('company');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campaign_id = (int)$_POST['campaign_id'];
    $company_id = $_SESSION['user_id'];
    
    // Verify ownership + status must be 'pending' + no reports exist
    $stmt = $conn->prepare("
        SELECT c.id, c.title,
        (SELECT COUNT(*) FROM reports WHERE campaign_id = c.id) as report_count
        FROM campaigns c 
        WHERE c.id = ? AND c.company_id = ? AND c.status = 'pending'
    ");
    $stmt->bind_param("ii", $campaign_id, $company_id);
    $stmt->execute();
    $campaign = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$campaign) {
        $_SESSION['flash_msg'] = "Campaign not found, not owned by you, or not in pending status.";
    } elseif ($campaign['report_count'] > 0) {
        $_SESSION['flash_msg'] = "Cannot delete campaign that already has reports submitted.";
    } else {
        // Safe to delete
        $stmt_del = $conn->prepare("DELETE FROM campaigns WHERE id = ? AND company_id = ? AND status = 'pending'");
        $stmt_del->bind_param("ii", $campaign_id, $company_id);
        $stmt_del->execute();
        
        if ($stmt_del->affected_rows > 0) {
            log_action('campaign_deleted', $company_id, "Campaign ID: $campaign_id, Title: {$campaign['title']}");
            $_SESSION['flash_msg'] = "Campaign \"{$campaign['title']}\" has been deleted.";
        } else {
            $_SESSION['flash_msg'] = "Failed to delete campaign (may have been modified concurrently).";
        }
        $stmt_del->close();
    }
}

header("Location: " . BASE_URL . "/company/my_campaigns.php");
exit;
