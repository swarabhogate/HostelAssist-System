<?php
session_start();
include("config.php");
include_once("system_helpers.php");

ensureComplaintWorkflowSchema($conn);

$role = isset($_SESSION['role']) ? strtolower(trim((string) $_SESSION['role'])) : '';
if ($role !== 'security') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$gatepassId = isset($_POST['gatepass_id']) ? (int) $_POST['gatepass_id'] : 0;
$action = isset($_POST['action']) ? trim((string) $_POST['action']) : '';

if ($gatepassId <= 0 || !in_array($action, ['submitted', 'returned'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Check current status
$stmt = mysqli_prepare($conn, "SELECT status, security_status FROM gate_pass WHERE gatepass_id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $gatepassId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$gatepass = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

if (!$gatepass) {
    echo json_encode(['success' => false, 'message' => 'Gatepass not found']);
    exit;
}

if (strtolower(trim((string) $gatepass['status'])) !== 'approved') {
    echo json_encode(['success' => false, 'message' => 'Gatepass is not approved']);
    exit;
}

$currentSecurityStatus = strtolower(trim((string) $gatepass['security_status']));

if ($action === 'submitted') {
    if ($currentSecurityStatus !== '') {
        echo json_encode(['success' => false, 'message' => 'Gatepass already processed']);
        exit;
    }
    
    $updateStmt = mysqli_prepare($conn, "UPDATE gate_pass SET security_status = 'submitted', submitted_at = NOW() WHERE gatepass_id = ?");
} else { // returned
    if ($currentSecurityStatus !== 'submitted') {
        echo json_encode(['success' => false, 'message' => 'Gatepass must be marked submitted first']);
        exit;
    }
    
    $updateStmt = mysqli_prepare($conn, "UPDATE gate_pass SET security_status = 'returned', returned_at = NOW() WHERE gatepass_id = ?");
}

if ($updateStmt) {
    mysqli_stmt_bind_param($updateStmt, "i", $gatepassId);
    $success = mysqli_stmt_execute($updateStmt);
    mysqli_stmt_close($updateStmt);
    
    if ($success) {
        // Also fetch the formatted times to return
        $timeStmt = mysqli_prepare($conn, "SELECT DATE_FORMAT(submitted_at, '%h:%i %p') as sub_time, DATE_FORMAT(returned_at, '%h:%i %p') as ret_time FROM gate_pass WHERE gatepass_id = ?");
        mysqli_stmt_bind_param($timeStmt, "i", $gatepassId);
        mysqli_stmt_execute($timeStmt);
        $timeResult = mysqli_stmt_get_result($timeStmt);
        $times = mysqli_fetch_assoc($timeResult);
        mysqli_stmt_close($timeStmt);

        echo json_encode([
            'success' => true, 
            'message' => 'Status updated successfully',
            'submitted_time' => $times['sub_time'] ?? '',
            'returned_time' => $times['ret_time'] ?? ''
        ]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Failed to update status']);
?>
