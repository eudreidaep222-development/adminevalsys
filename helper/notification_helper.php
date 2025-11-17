<?php
function addNotification($conn, $userId, $title, $message, $type = 'info', $meta = null) {
    $metaJson = $meta ? json_encode($meta) : null;
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, meta) VALUES (?, ?, ?, ?, ?)");
    if ($userId === null) {
        $stmt->bind_param('issss', $userId, $title, $message, $type, $metaJson);
        $userId = null; // Broadcast
    } else {
        $stmt->bind_param('issss', $userId, $title, $message, $type, $metaJson);
    }
    return $stmt->execute();
}

function getNotifications($conn, $userId, $limit = 10) {
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? OR user_id IS NULL ORDER BY created_at DESC LIMIT ?");
    $stmt->bind_param('ii', $userId, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function markNotificationRead($conn, $notifId) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $stmt->bind_param('i', $notifId);
    return $stmt->execute();
}
