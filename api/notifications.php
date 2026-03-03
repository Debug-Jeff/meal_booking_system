<?php
// =====================================================
// API: Notifications (count + mark-read)
// =====================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/notifications.php';

header('Content-Type: application/json');

// Must be logged in
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$uid    = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($action === 'count') {
    echo json_encode(['count' => getUnreadCount($conn, $uid)]);

} elseif ($action === 'mark_read') {
    markAllRead($conn, $uid);
    echo json_encode(['ok' => true]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Unknown action']);
}
