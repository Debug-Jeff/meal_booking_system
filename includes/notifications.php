<?php
// =====================================================
// NOTIFICATION HELPERS
// =====================================================

/**
 * Insert a single notification for one user.
 */
function createNotification(mysqli $conn, int $user_id, string $type, string $message, string $link = ''): void {
    $stmt = $conn->prepare(
        "INSERT INTO notifications (user_id, type, message, link) VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("isss", $user_id, $type, $message, $link);
    $stmt->execute();
}

/**
 * Notify every admin and super_admin user.
 */
function notifyAllAdmins(mysqli $conn, string $type, string $message, string $link = ''): void {
    $res = $conn->query("SELECT id FROM users WHERE role IN ('admin','super_admin')");
    while ($row = $res->fetch_assoc()) {
        createNotification($conn, (int)$row['id'], $type, $message, $link);
    }
}

/**
 * Count unread notifications for a user.
 */
function getUnreadCount(mysqli $conn, int $user_id): int {
    $stmt = $conn->prepare("SELECT COUNT(*) c FROM notifications WHERE user_id=? AND is_read=0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return (int)$stmt->get_result()->fetch_assoc()['c'];
}

/**
 * Fetch latest notifications for a user.
 */
function getRecentNotifications(mysqli $conn, int $user_id, int $limit = 10): array {
    $stmt = $conn->prepare(
        "SELECT id, type, message, link, is_read, created_at
         FROM notifications
         WHERE user_id = ?
         ORDER BY created_at DESC
         LIMIT ?"
    );
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Mark all notifications as read for a user.
 */
function markAllRead(mysqli $conn, int $user_id): void {
    $stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}
