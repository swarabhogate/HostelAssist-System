<?php
session_start();
include("config.php");
include_once("system_helpers.php");

ensureComplaintWorkflowSchema($conn);

$role = isset($_SESSION['role']) ? strtolower(trim((string) $_SESSION['role'])) : '';
$studentId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
$complaintId = isset($_POST['complaint_id']) ? (int) $_POST['complaint_id'] : 0;

if ($role !== 'student' || $studentId <= 0 || $complaintId <= 0) {
    header("Location: Student_home.php");
    exit;
}

// Verify the complaint belongs to this student AND has a deletable status
$selectStmt = mysqli_prepare($conn, "SELECT complaint_id, status FROM complaints WHERE complaint_id = ? AND student_id = ?");
if (!$selectStmt) {
    header("Location: complaint_info.php?id=" . $complaintId);
    exit;
}

mysqli_stmt_bind_param($selectStmt, "ii", $complaintId, $studentId);
mysqli_stmt_execute($selectStmt);
$result = mysqli_stmt_get_result($selectStmt);
$complaint = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($selectStmt);

if (!$complaint) {
    header("Location: Student_home.php");
    exit;
}

// Only allow deletion if complaint is Resolved
$statusLower = strtolower(trim((string) ($complaint['status'] ?? '')));
if ($statusLower !== 'resolved') {
    header("Location: complaint_info.php?id=" . $complaintId);
    exit;
}

// Soft delete: mark as hidden for student only, record remains for Warden
$updateStmt = mysqli_prepare($conn, "UPDATE complaints SET deleted_by_student = 1 WHERE complaint_id = ? AND student_id = ?");
if ($updateStmt) {
    mysqli_stmt_bind_param($updateStmt, "ii", $complaintId, $studentId);
    mysqli_stmt_execute($updateStmt);
    mysqli_stmt_close($updateStmt);
}

header("Location: Student_home.php");
exit;
?>
