<?php
session_start();
include '../../1database_conn/connection.php';

// Check logged-in user
$userId = $_SESSION['user_id'] ?? 0;

// Fetch notifications for user
$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Notifications</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

</head>
<body>
<h2>Your Notifications</h2>
<ul class="list-group">
    <?php foreach ($notifications as $n): ?>
        <li class="list-group-item <?= $n['is_read'] == 0 ? 'fw-bold' : '' ?>">
            <?= htmlspecialchars($n['title']) ?><br>
            <small><?= htmlspecialchars($n['message']) ?></small><br>
            <small class="text-muted"><?= date("M d, Y H:i", strtotime($n['created_at'])) ?></small>
        </li>
    <?php endforeach; ?>
</ul>
</body>
</html>
