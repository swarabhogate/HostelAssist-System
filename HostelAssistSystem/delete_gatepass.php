<?php
session_start();
include("config.php");
include_once("system_helpers.php");

ensureComplaintWorkflowSchema($conn);

$role = isset($_SESSION['role']) ? strtolower(trim((string) $_SESSION['role'])) : '';
$studentId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
$gatepassId = isset($_POST['gatepass_id']) ? (int) $_POST['gatepass_id'] : 0;

if ($role !== 'student' || $studentId <= 0 || $gatepassId <= 0) {
    header("Location: Student_home.php");
    exit;
}

// Verify the gatepass belongs to this student AND has a deletable status
$selectStmt = mysqli_prepare($conn, "SELECT gatepass_id, status FROM gate_pass WHERE gatepass_id = ? AND student_id = ?");
if (!$selectStmt) {
    header("Location: view_gatepass.php?id=" . $gatepassId);
    exit;
}

mysqli_stmt_bind_param($selectStmt, "ii", $gatepassId, $studentId);
mysqli_stmt_execute($selectStmt);
$result = mysqli_stmt_get_result($selectStmt);
$gatepass = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($selectStmt);

if (!$gatepass) {
    header("Location: Student_home.php");
    exit;
}

// Only allow deletion if gatepass is Approved or Rejected
$statusLower = strtolower(trim((string) ($gatepass['status'] ?? '')));
if (!in_array($statusLower, ['approved', 'rejected'], true)) {
    header("Location: view_gatepass.php?id=" . $gatepassId);
    exit;
}

// Soft delete: mark as hidden for student only, record remains for HOD/Warden
$updateStmt = mysqli_prepare($conn, "UPDATE gate_pass SET deleted_by_student = 1 WHERE gatepass_id = ? AND student_id = ?");
if ($updateStmt) {
    mysqli_stmt_bind_param($updateStmt, "ii", $gatepassId, $studentId);
    mysqli_stmt_execute($updateStmt);
    mysqli_stmt_close($updateStmt);
}

header("Location: Student_home.php");
exit;
?>
