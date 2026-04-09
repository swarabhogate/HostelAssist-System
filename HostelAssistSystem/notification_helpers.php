<?php
if (!function_exists('normalizeRoleName')) {
    function normalizeRoleName($role)
    {
        return strtolower(trim((string) $role));
    }
}

if (!function_exists('getFacultyDatabaseRole')) {
    function getFacultyDatabaseRole($role)
    {
        $normalizedRole = normalizeRoleName($role);

        if ($normalizedRole === 'hod') {
            return 'HOD';
        }

        if ($normalizedRole === 'warden') {
            return 'Warden';
        }

        return ucfirst($normalizedRole);
    }
}

if (!function_exists('ensureNotificationsTable')) {
    function ensureNotificationsTable($conn)
    {
        static $initialized = false;

        if ($initialized) {
            return;
        }

        $sql = "CREATE TABLE IF NOT EXISTS notifications (
                    notification_id INT AUTO_INCREMENT PRIMARY KEY,
                    recipient_role VARCHAR(50) NOT NULL,
                    recipient_id INT NOT NULL,
                    actor_role VARCHAR(50) DEFAULT NULL,
                    actor_id INT DEFAULT NULL,
                    title VARCHAR(255) NOT NULL,
                    message TEXT NOT NULL,
                    entity_type VARCHAR(50) NOT NULL,
                    entity_id INT NOT NULL,
                    target_url VARCHAR(255) NOT NULL,
                    is_read TINYINT(1) NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_recipient (recipient_role, recipient_id, is_read),
                    INDEX idx_entity (entity_type, entity_id)
                )";

        mysqli_query($conn, $sql);
        $initialized = true;
    }
}

if (!function_exists('createNotification')) {
    function createNotification($conn, $recipientRole, $recipientId, $title, $message, $entityType, $entityId, $targetUrl, $actorRole = null, $actorId = null)
    {
        ensureNotificationsTable($conn);

        $recipientRole = normalizeRoleName($recipientRole);
        $actorRole = $actorRole !== null ? normalizeRoleName($actorRole) : null;
        $recipientId = (int) $recipientId;
        $entityId = (int) $entityId;
        $actorId = $actorId !== null ? (int) $actorId : null;

        if ($recipientRole === '' || $recipientId <= 0 || $entityType === '' || $entityId <= 0 || $targetUrl === '') {
            return false;
        }

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO notifications
            (recipient_role, recipient_id, actor_role, actor_id, title, message, entity_type, entity_id, target_url)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param(
            $stmt,
            "sisisssis",
            $recipientRole,
            $recipientId,
            $actorRole,
            $actorId,
            $title,
            $message,
            $entityType,
            $entityId,
            $targetUrl
        );

        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $success;
    }
}

if (!function_exists('notifyFacultyByRole')) {
    function notifyFacultyByRole($conn, $role, $title, $message, $entityType, $entityId, $targetUrl, $department = null, $actorRole = null, $actorId = null)
    {
        ensureNotificationsTable($conn);

        $facultyRole = getFacultyDatabaseRole($role);
        if ($facultyRole === '') {
            return;
        }

        if ($department !== null && trim($department) !== '') {
            $department = trim($department);
            $stmt = mysqli_prepare($conn, "SELECT staff_id FROM faculty WHERE role = ? AND LOWER(TRIM(department)) = LOWER(TRIM(?))");
            mysqli_stmt_bind_param($stmt, "ss", $facultyRole, $department);
        } else {
            $stmt = mysqli_prepare($conn, "SELECT staff_id FROM faculty WHERE role = ?");
            mysqli_stmt_bind_param($stmt, "s", $facultyRole);
        }

        if (!$stmt) {
            return;
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                createNotification(
                    $conn,
                    normalizeRoleName($role),
                    (int) $row['staff_id'],
                    $title,
                    $message,
                    $entityType,
                    $entityId,
                    $targetUrl,
                    $actorRole,
                    $actorId
                );
            }
        }

        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('clearNotificationsForUser')) {
    function clearNotificationsForUser($conn, $role, $userId)
    {
        ensureNotificationsTable($conn);

        $role = normalizeRoleName($role);
        $userId = (int) $userId;

        if ($role === '' || $userId <= 0) {
            return false;
        }

        $stmt = mysqli_prepare($conn, "DELETE FROM notifications WHERE recipient_role = ? AND recipient_id = ?");
        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, "si", $role, $userId);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $success;
    }
}

if (!function_exists('clearNotificationsByEntity')) {
    function clearNotificationsByEntity($conn, $entityType, $entityId)
    {
        ensureNotificationsTable($conn);

        $entityType = strtolower(trim((string) $entityType));
        $entityId = (int) $entityId;

        if ($entityType === '' || $entityId <= 0) {
            return false;
        }

        $stmt = mysqli_prepare($conn, "DELETE FROM notifications WHERE entity_type = ? AND entity_id = ?");
        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, "si", $entityType, $entityId);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $success;
    }
}

if (!function_exists('getNotificationCount')) {
    function getNotificationCount($conn, $role, $userId)
    {
        ensureNotificationsTable($conn);

        $role = normalizeRoleName($role);
        $userId = (int) $userId;
        if ($role === '' || $userId <= 0) {
            return 0;
        }

        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM notifications WHERE recipient_role = ? AND recipient_id = ? AND is_read = 0");
        if (!$stmt) {
            return 0;
        }

        mysqli_stmt_bind_param($stmt, "si", $role, $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $count = 0;

        if ($result && ($row = mysqli_fetch_assoc($result))) {
            $count = (int) $row['total'];
        }

        mysqli_stmt_close($stmt);
        return $count;
    }
}

if (!function_exists('getNotificationsForUser')) {
    function getNotificationsForUser($conn, $role, $userId)
    {
        ensureNotificationsTable($conn);

        $role = normalizeRoleName($role);
        $userId = (int) $userId;
        $notifications = [];

        if ($role === '' || $userId <= 0) {
            return $notifications;
        }

        $stmt = mysqli_prepare(
            $conn,
            "SELECT notification_id, title, message, entity_type, entity_id, target_url, is_read, created_at
             FROM notifications
             WHERE recipient_role = ? AND recipient_id = ?
             ORDER BY created_at DESC, notification_id DESC"
        );

        if (!$stmt) {
            return $notifications;
        }

        mysqli_stmt_bind_param($stmt, "si", $role, $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result) {
            $notifications = mysqli_fetch_all($result, MYSQLI_ASSOC);
        }

        mysqli_stmt_close($stmt);
        return $notifications;
    }
}

if (!function_exists('markNotificationAsRead')) {
    function markNotificationAsRead($conn, $notificationId, $role, $userId)
    {
        ensureNotificationsTable($conn);

        $notificationId = (int) $notificationId;
        $role = normalizeRoleName($role);
        $userId = (int) $userId;

        if ($notificationId <= 0 || $role === '' || $userId <= 0) {
            return null;
        }

        $selectStmt = mysqli_prepare(
            $conn,
            "SELECT target_url
             FROM notifications
             WHERE notification_id = ? AND recipient_role = ? AND recipient_id = ?"
        );

        if (!$selectStmt) {
            return null;
        }

        mysqli_stmt_bind_param($selectStmt, "isi", $notificationId, $role, $userId);
        mysqli_stmt_execute($selectStmt);
        $result = mysqli_stmt_get_result($selectStmt);
        $notification = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($selectStmt);

        if (!$notification) {
            return null;
        }

        $updateStmt = mysqli_prepare(
            $conn,
            "UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND recipient_role = ? AND recipient_id = ?"
        );

        if ($updateStmt) {
            mysqli_stmt_bind_param($updateStmt, "isi", $notificationId, $role, $userId);
            mysqli_stmt_execute($updateStmt);
            mysqli_stmt_close($updateStmt);
        }

        return $notification['target_url'];
    }
}

if (!function_exists('buildNotificationTargetUrl')) {
    function buildNotificationTargetUrl($entityType, $entityId)
    {
        $entityType = strtolower(trim((string) $entityType));
        $entityId = (int) $entityId;

        if ($entityType === 'gatepass') {
            return 'view_gatepass.php?id=' . $entityId;
        }

        if ($entityType === 'complaint') {
            return 'complaint_info.php?id=' . $entityId;
        }

        return 'notification.php';
    }
}
?>
