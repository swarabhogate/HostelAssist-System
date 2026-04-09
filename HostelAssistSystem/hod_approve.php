<?php
session_start();
include('config.php');
include('notification_helpers.php');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
$hodDepartment = isset($_SESSION['department']) ? trim((string) $_SESSION['department']) : '';

if ($role !== 'hod') {
    header("Location: login.html");
    exit;
}

$checkSql = "SELECT s.department, s.student_id, s.name, gp.status
             FROM gate_pass gp
             JOIN students s ON gp.student_id = s.student_id
             WHERE gp.gatepass_id = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("i", $id);
$checkStmt->execute();
$gatepass = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

if (!$gatepass || $hodDepartment === '' || strcasecmp(trim((string) $gatepass['department']), trim((string) $hodDepartment)) !== 0) {
    $_SESSION['gatepass_flash'] = "You are not allowed to approve this gatepass.";
    header("Location: hod_home.php");
    exit;
}

// Verify gatepass is in Pending HOD status
if (strtolower(trim((string) $gatepass['status'])) !== 'pending hod') {
    $_SESSION['gatepass_flash'] = "This gatepass is not pending HOD approval.";
    header("Location: hod_home.php");
    exit;
}

$sql = "UPDATE gate_pass SET status='Pending Warden', hod_approved=1 WHERE gatepass_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$targetUrl = buildNotificationTargetUrl('gatepass', $id);
$studentId = (int) $gatepass['student_id'];
$studentName = !empty($gatepass['name']) ? $gatepass['name'] : 'Student';
$actorId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

createNotification(
    $conn,
    'student',
    $studentId,
    'Gatepass approved by HOD',
    'Your gatepass GP#' . $id . ' has been approved by HOD and forwarded to the warden.',
    'gatepass',
    $id,
    $targetUrl,
    'hod',
    $actorId
);

notifyFacultyByRole(
    $conn,
    'warden',
    'Gatepass forwarded by HOD',
    'HOD approved gatepass GP#' . $id . ' for ' . $studentName . '.',
    'gatepass',
    $id,
    $targetUrl,
    null,
    'hod',
    $actorId
);

$_SESSION['gatepass_flash'] = "Gatepass approved by HOD and sent to Warden.";

header("Location: hod_home.php");
exit;
?>
