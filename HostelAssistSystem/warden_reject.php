<?php
session_start();
include('config.php');
include('notification_helpers.php');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';

if ($role !== 'warden') {
    header("Location: login.html");
    exit;
}

$gatepassStmt = $conn->prepare("SELECT student_id, status FROM gate_pass WHERE gatepass_id = ?");
$gatepassStmt->bind_param("i", $id);
$gatepassStmt->execute();
$gatepass = $gatepassStmt->get_result()->fetch_assoc();
$gatepassStmt->close();

if (!$gatepass) {
    $_SESSION['gatepass_flash'] = "Gatepass not found.";
    header("Location: warden_home.php");
    exit;
}

if (strtolower(trim((string) $gatepass['status'])) !== 'pending warden') {
    $_SESSION['gatepass_flash'] = "This gatepass is not pending warden approval.";
    header("Location: warden_home.php");
    exit;
}

$sql = "UPDATE gate_pass SET status='Rejected' WHERE gatepass_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

createNotification(
    $conn,
    'student',
    (int) $gatepass['student_id'],
    'Gatepass rejected by Warden',
    'Your gatepass GP#' . $id . ' has been rejected by the warden.',
    'gatepass',
    $id,
    buildNotificationTargetUrl('gatepass', $id),
    'warden',
    isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null
);

$_SESSION['gatepass_flash'] = "Gatepass rejected successfully.";

header("Location: view_gatepass.php?id=".$id);
exit;
?>
