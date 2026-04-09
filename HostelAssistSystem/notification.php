<?php
session_start();
include("config.php");
include("notification_helpers.php");

$role = isset($_SESSION['role']) ? normalizeRoleName($_SESSION['role']) : '';
$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;

if ($role === '' || $userId <= 0) {
    header("Location: login.html");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_notifications'])) {
    clearNotificationsForUser($conn, $role, $userId);
    header("Location: notification.php");
    exit;
}

if (isset($_GET['open'])) {
    $targetUrl = markNotificationAsRead($conn, (int) $_GET['open'], $role, $userId);
    header("Location: " . ($targetUrl ?: "notification.php"));
    exit;
}

$notifications = getNotificationsForUser($conn, $role, $userId);

if ($role === 'student') {
    $backUrl = 'student_home.php';
} elseif ($role === 'hod') {
    $backUrl = 'hod_home.php';
} else {
    $backUrl = 'warden_home.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications</title>
<style>
body {
    margin: 0;
    font-family: 'Segoe UI', Arial, sans-serif;
    background: linear-gradient(135deg, #e6f4f7, #d4eef3);
    color: #16333a;
}

.page {
    max-width: 900px;
    margin: 40px auto;
    padding: 0 20px 40px;
}

.card {
    background: #ffffff;
    border-radius: 18px;
    padding: 28px;
    box-shadow: 0 12px 32px rgba(5, 122, 141, 0.12);
}

h1 {
    margin: 0 0 20px;
    color: #057a8d;
}

.notification-item {
    display: block;
    text-decoration: none;
    color: inherit;
    padding: 18px;
    border-radius: 14px;
    margin-bottom: 14px;
    background: #f8fcff;
    border: 1px solid #d8ecf1;
    transition: 0.2s ease;
}

.notification-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 18px rgba(5, 122, 141, 0.12);
}

.notification-item.unread {
    border-left: 5px solid #057a8d;
    background: #eefbfe;
}

.meta {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 8px;
    align-items: center;
}

.title {
    font-weight: 700;
    color: #0f5f6b;
}

.time {
    font-size: 13px;
    color: #6b7f85;
}

.message {
    margin: 0;
    line-height: 1.5;
}

.empty {
    padding: 32px 20px;
    text-align: center;
    background: #f8fcff;
    border-radius: 14px;
    color: #5d7177;
}

.actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-top: 20px;
}

.back-link {
    display: inline-block;
    padding: 11px 18px;
    border-radius: 10px;
    background: #057a8d;
    color: #fff;
    text-decoration: none;
}

.clear-btn {
    padding: 11px 18px;
    border-radius: 10px;
    border: 1px solid #d9534f;
    background: #fff5f5;
    color: #c0392b;
    cursor: pointer;
    font-weight: 600;
}
</style>
</head>
<body>
<div class="page">
    <div class="card">
        <h1>Notifications</h1>

        <?php if (!empty($notifications)) { ?>
            <?php foreach ($notifications as $notification) { ?>
                <a class="notification-item <?php echo (int) $notification['is_read'] === 0 ? 'unread' : ''; ?>" href="notification.php?open=<?php echo (int) $notification['notification_id']; ?>">
                    <div class="meta">
                        <div class="title"><?php echo htmlspecialchars($notification['title']); ?></div>
                        <div class="time"><?php echo htmlspecialchars(date("d M Y, h:i A", strtotime($notification['created_at']))); ?></div>
                    </div>
                    <p class="message"><?php echo htmlspecialchars($notification['message']); ?></p>
                </a>
            <?php } ?>
        <?php } else { ?>
            <div class="empty">No notifications available right now.</div>
        <?php } ?>

        <div class="actions">
            <form method="POST">
                <button type="submit" name="clear_notifications" class="clear-btn">Clear Notifications</button>
            </form>
            <a class="back-link" href="<?php echo htmlspecialchars($backUrl); ?>">Back to Dashboard</a>
        </div>
    </div>
</div>
</body>
</html>
