<?php
include_once("system_helpers.php");

if (!function_exists('getProfileBackUrl')) {
    function getProfileBackUrl($role)
    {
        $role = strtolower(trim((string) $role));
        if ($role === 'student') {
            return 'student_home.php';
        }

        if ($role === 'hod') {
            return 'hod_home.php';
        }

        return 'warden_home.php';
    }
}

if (!function_exists('loadProfileContext')) {
    function loadProfileContext($conn)
    {
        ensureColumnExists($conn, 'students', 'year', "VARCHAR(50) NULL AFTER department");
        ensureColumnExists($conn, 'faculty', 'photo', "VARCHAR(255) NULL AFTER password");

        $role = isset($_SESSION['role']) ? strtolower(trim((string) $_SESSION['role'])) : '';
        $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;

        if ($role === '' || $userId <= 0) {
            header("Location: login.html");
            exit;
        }

        $isStudent = $role === 'student';
        $table = $isStudent ? 'students' : 'faculty';
        $idColumn = $isStudent ? 'student_id' : 'staff_id';
        $backUrl = getProfileBackUrl($role);

        $profileSql = $isStudent
            ? "SELECT student_id, name, email, mobile, parent_mobile1, parent_mobile2, department, `year`, semester, room_number, photo FROM students WHERE student_id = ?"
            : "SELECT staff_id, name, email, mobile, department, role, photo FROM faculty WHERE staff_id = ?";

        $profileStmt = mysqli_prepare($conn, $profileSql);
        mysqli_stmt_bind_param($profileStmt, 'i', $userId);
        mysqli_stmt_execute($profileStmt);
        $profileResult = mysqli_stmt_get_result($profileStmt);
        $row = $profileResult ? mysqli_fetch_assoc($profileResult) : null;
        mysqli_stmt_close($profileStmt);

        if (!$row) {
            die('Profile not found.');
        }

        $photoFile = !empty($row['photo']) ? 'uploads/' . $row['photo'] : 'images/default-profile.svg';

        return [
            'role' => $role,
            'userId' => $userId,
            'isStudent' => $isStudent,
            'table' => $table,
            'idColumn' => $idColumn,
            'backUrl' => $backUrl,
            'row' => $row,
            'photoFile' => $photoFile,
        ];
    }
}
?>
