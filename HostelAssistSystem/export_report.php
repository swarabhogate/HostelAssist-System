<?php
session_start();
include("config.php");
include_once("system_helpers.php");

ensureComplaintWorkflowSchema($conn);

$role = isset($_SESSION['role']) ? strtolower(trim((string) $_SESSION['role'])) : '';
$reportType = trim((string) ($_GET['type'] ?? ''));

// Warden → complaints + gatepasses | HOD → gatepasses only (dept-scoped) | Security → gatepasses only (approved)
$wardenAllowed = ($role === 'warden' && in_array($reportType, ['complaints', 'gatepasses'], true));
$hodAllowed = ($role === 'hod' && $reportType === 'gatepasses');
$securityAllowed = ($role === 'security' && $reportType === 'gatepasses');

if (!$wardenAllowed && !$hodAllowed && !$securityAllowed) {
    header("Location: login.html");
    exit;
}

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename={$reportType}_report_" . date('Ymd_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

$output = fopen('php://output', 'w');

if ($reportType === 'complaints') {
    // Warden sees all complaints
    fputcsv($output, ['Complaint ID', 'Student Name', 'Department', 'Title', 'Status', 'Remark', 'Submitted On', 'Completed On']);
    $sql = "SELECT c.complaint_id, s.name, s.department, c.title, c.status, c.remark, c.submission_date, c.completion_date
            FROM complaints c
            JOIN students s ON c.student_id = s.student_id
            ORDER BY c.complaint_id DESC";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($output, [
                $row['complaint_id'],
                $row['name'],
                $row['department'],
                $row['title'],
                $row['status'],
                $row['remark'] ?? '',
                $row['submission_date'] ?? '',
                $row['completion_date'] ?? '',
            ]);
        }
    }
} else {
    if ($role === 'hod') {
        // HOD: Show HOD Status column instead of Warden Approved
        fputcsv($output, ['Gatepass ID', 'Student Name', 'Department', 'Reason', 'Location', 'Leaving Date', 'Return Date', 'HOD Status']);

        // HOD: only their department's gatepasses — show only HOD-relevant statuses:
        // 1) Pending HOD (awaiting HOD decision)
        // 2) HOD Approved → now Pending Warden (hod_approved = 1)
        // 3) HOD Rejected (status = rejected AND hod_approved = 0, means HOD rejected it)
        $hodDepartment = isset($_SESSION['department']) ? trim((string) $_SESSION['department']) : '';
        $stmt = $conn->prepare(
            "SELECT gp.gatepass_id, s.name, s.department, gp.reason, gp.location, gp.date_going, gp.date_return, gp.status, gp.hod_approved
             FROM gate_pass gp
             JOIN students s ON gp.student_id = s.student_id
             WHERE LOWER(TRIM(s.department)) = LOWER(TRIM(?))
               AND (
                     LOWER(TRIM(gp.status)) = 'pending hod'
                  OR (LOWER(TRIM(gp.status)) = 'pending warden' AND gp.hod_approved = 1)
                  OR (LOWER(TRIM(gp.status)) = 'approved' AND gp.hod_approved = 1)
                  OR (LOWER(TRIM(gp.status)) = 'rejected' AND gp.hod_approved = 0)
               )
             ORDER BY gp.gatepass_id DESC"
        );
        if ($stmt) {
            $stmt->bind_param("s", $hodDepartment);
            $stmt->execute();
            $result = $stmt->get_result();

            if (!empty($result)) {
                while ($row = $result->fetch_assoc()) {
                    // Determine HOD Status label
                    $hodApprovedFlag = (int) ($row['hod_approved'] ?? 0);
                    $gpStatusLower = strtolower(trim((string) $row['status']));

                    if ($gpStatusLower === 'pending hod') {
                        $hodStatus = 'Pending';
                    } elseif (($gpStatusLower === 'pending warden' || $gpStatusLower === 'approved') && $hodApprovedFlag === 1) {
                        $hodStatus = 'Approved';
                    } elseif ($gpStatusLower === 'rejected' && $hodApprovedFlag === 0) {
                        $hodStatus = 'Rejected';
                    } else {
                        $hodStatus = ucfirst($gpStatusLower);
                    }

                    fputcsv($output, [
                        $row['gatepass_id'],
                        $row['name'],
                        $row['department'],
                        $row['reason'],
                        $row['location'],
                        $row['date_going'] ?? '',
                        $row['date_return'] ?? '',
                        $hodStatus
                    ]);
                }
            }
            $stmt->close();
        }
    } elseif ($role === 'security') {
        // Security: Show only Approved gatepasses and include security tracking timestamps
        fputcsv($output, ['Gatepass ID', 'Student Name', 'Department', 'Reason', 'Location', 'Leaving Date', 'Return Date', 'Security Status', 'Time Left', 'Time Returned']);

        $result = mysqli_query(
            $conn,
            "SELECT gp.gatepass_id, s.name, s.department, gp.reason, gp.location, gp.date_going, gp.date_return, gp.security_status, gp.submitted_at, gp.returned_at
             FROM gate_pass gp
             JOIN students s ON gp.student_id = s.student_id
             WHERE LOWER(TRIM(gp.status)) = 'approved'
             ORDER BY gp.gatepass_id DESC"
        );

        if (!empty($result)) {
            while ($row = $result instanceof mysqli_result ? $result->fetch_assoc() : mysqli_fetch_assoc($result)) {
                $statusFormatted = empty($row['security_status']) ? 'Waiting' : ucfirst(strtolower(trim((string) $row['security_status'])));
                $timeLeft = !empty($row['submitted_at']) ? date('h:i A', strtotime($row['submitted_at'])) : '';
                $timeReturn = !empty($row['returned_at']) ? date('h:i A', strtotime($row['returned_at'])) : '';

                fputcsv($output, [
                    $row['gatepass_id'],
                    $row['name'],
                    $row['department'],
                    $row['reason'],
                    $row['location'],
                    $row['date_going'] ?? '',
                    $row['date_return'] ?? '',
                    $statusFormatted,
                    $timeLeft,
                    $timeReturn
                ]);
            }
        }
    } else {
        // Warden: Show all columns including Warden Approved
        fputcsv($output, ['Gatepass ID', 'Student Name', 'Department', 'Reason', 'Location', 'Leaving Date', 'Return Date', 'Status', 'HOD Approved', 'Warden Approved']);

        // Warden: all gatepasses
        $result = mysqli_query(
            $conn,
            "SELECT gp.gatepass_id, s.name, s.department, gp.reason, gp.location, gp.date_going, gp.date_return, gp.status, gp.hod_approved, gp.warden_approved
             FROM gate_pass gp
             JOIN students s ON gp.student_id = s.student_id
             ORDER BY gp.gatepass_id DESC"
        );

        if (!empty($result)) {
            while ($row = $result instanceof mysqli_result ? $result->fetch_assoc() : mysqli_fetch_assoc($result)) {
                fputcsv($output, [
                    $row['gatepass_id'],
                    $row['name'],
                    $row['department'],
                    $row['reason'],
                    $row['location'],
                    $row['date_going'] ?? '',
                    $row['date_return'] ?? '',
                    $row['status'],
                    $row['hod_approved'] ? 'Yes' : 'No',
                    $row['warden_approved'] ? 'Yes' : 'No',
                ]);
            }
        }
    }
}

fclose($output);
exit;
?>