<?php
session_start();
include("config.php");
include_once("system_helpers.php");

ensureComplaintWorkflowSchema($conn);

$role = isset($_SESSION['role']) ? strtolower(trim((string) $_SESSION['role'])) : '';
$userDepartment = isset($_SESSION['department']) ? trim((string) $_SESSION['department']) : '';
$recordType = trim((string) ($_POST['record_type'] ?? ''));
$selectedIds = isset($_POST['selected_ids']) && is_array($_POST['selected_ids']) ? $_POST['selected_ids'] : [];
$ids = array_values(array_filter(array_map('intval', $selectedIds)));

if (!in_array($role, ['warden', 'hod'], true) || empty($ids) || !in_array($recordType, ['complaint', 'gatepass'], true)) {
    header("Location: " . ($role === 'hod' ? 'hod_home.php' : 'warden_home.php'));
    exit;
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('i', count($ids));

if ($recordType === 'complaint') {
    // Only warden can bulk-delete complaints (no HOD complaint delete UI)
    if ($role === 'warden') {
        // Only allow soft-delete of Resolved complaints
        $sql = "SELECT complaint_id FROM complaints WHERE complaint_id IN ($placeholders) AND LOWER(TRIM(status)) = 'resolved'";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, $types, ...$ids);
        }
    } else {
        // HOD has no complaint delete — redirect gracefully
        header("Location: hod_home.php");
        exit;
    }

    $complaints = [];
    if (isset($stmt) && $stmt) {
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result) {
            $complaints = mysqli_fetch_all($result, MYSQLI_ASSOC);
        }
        mysqli_stmt_close($stmt);
    }

    if (!empty($complaints)) {
        $allowedIds = array_map('intval', array_column($complaints, 'complaint_id'));
        $allowedPlaceholders = implode(',', array_fill(0, count($allowedIds), '?'));

        // Soft delete: mark as hidden for warden only
        $updateStmt = mysqli_prepare($conn, "UPDATE complaints SET deleted_by_warden = 1 WHERE complaint_id IN ($allowedPlaceholders)");
        if ($updateStmt) {
            mysqli_stmt_bind_param($updateStmt, str_repeat('i', count($allowedIds)), ...$allowedIds);
            mysqli_stmt_execute($updateStmt);
            mysqli_stmt_close($updateStmt);
        }
    }
} else {
    // Gatepass delete — role-specific soft delete column
    // Only allow soft-delete of Approved or Rejected gatepasses
    if ($role === 'hod') {
        $sql = "SELECT gp.gatepass_id
                FROM gate_pass gp
                JOIN students s ON gp.student_id = s.student_id
                WHERE gp.gatepass_id IN ($placeholders)
                AND LOWER(TRIM(s.department)) = LOWER(TRIM(?))
                AND LOWER(TRIM(gp.status)) IN ('approved', 'rejected')";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            $bindTypes = $types . 's';
            $bindParams = array_merge($ids, [$userDepartment]);
            mysqli_stmt_bind_param($stmt, $bindTypes, ...$bindParams);
        }
    } else {
        $sql = "SELECT gatepass_id FROM gate_pass WHERE gatepass_id IN ($placeholders) AND LOWER(TRIM(status)) IN ('approved', 'rejected')";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, $types, ...$ids);
        }
    }

    $gatepasses = [];
    if (isset($stmt) && $stmt) {
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result) {
            $gatepasses = mysqli_fetch_all($result, MYSQLI_ASSOC);
        }
        mysqli_stmt_close($stmt);
    }

    if (!empty($gatepasses)) {
        $allowedIds = array_map('intval', array_column($gatepasses, 'gatepass_id'));
        $allowedPlaceholders = implode(',', array_fill(0, count($allowedIds), '?'));

        // Soft delete: set the role-specific flag only
        $deleteColumn = ($role === 'hod') ? 'deleted_by_hod' : 'deleted_by_warden';
        $updateStmt = mysqli_prepare($conn, "UPDATE gate_pass SET `$deleteColumn` = 1 WHERE gatepass_id IN ($allowedPlaceholders)");
        if ($updateStmt) {
            mysqli_stmt_bind_param($updateStmt, str_repeat('i', count($allowedIds)), ...$allowedIds);
            mysqli_stmt_execute($updateStmt);
            mysqli_stmt_close($updateStmt);
        }
    }
}

header("Location: " . ($role === 'hod' ? 'hod_home.php' : 'warden_home.php'));
exit;
?>
