<?php
require_once __DIR__ . '/../config/db.php';

function log_action($action_type, $user_id = null, $details = '') {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $conn->prepare("INSERT INTO security_logs (action_type, user_id, ip_address, user_agent, details) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sisss", $action_type, $user_id, $ip, $ua, $details);
    $stmt->execute();
    $stmt->close();
}

function get_severity_badge($severity) {
    $classes = [
        'Low' => 'badge-low',
        'Medium' => 'badge-medium',
        'High' => 'badge-high',
        'Critical' => 'badge-critical'
    ];
    $class = $classes[$severity] ?? 'badge-low';
    return '<span class="badge ' . $class . '">' . htmlspecialchars($severity) . '</span>';
}

function get_status_badge($status) {
    $classes = [
        'Pending' => 'badge-gray',
        'Triaged' => 'badge-blue',
        'Resolved' => 'badge-green',
        'Duplicate' => 'badge-yellow',
        'Rejected' => 'badge-red',
        'active' => 'badge-green',
        'closed' => 'badge-gray',
        'pending' => 'badge-yellow',
        'open' => 'badge-yellow'
    ];
    $class = $classes[$status] ?? 'badge-gray';
    return '<span class="badge ' . $class . '">' . htmlspecialchars($status) . '</span>';
}

function format_points($points) {
    return number_format($points) . ' pts';
}

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}
