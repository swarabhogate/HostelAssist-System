<?php
if (!function_exists('ensureColumnExists')) {
    function ensureColumnExists($conn, $table, $column, $definition)
    {
        $table = mysqli_real_escape_string($conn, $table);
        $column = mysqli_real_escape_string($conn, $column);
        $result = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
        if ($result && mysqli_num_rows($result) === 0) {
            mysqli_query($conn, "ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        }
    }
}

if (!function_exists('normalizeEmailAddress')) {
    function normalizeEmailAddress($email)
    {
        return strtolower(trim((string) $email));
    }
}

if (!function_exists('ensureAdminsTable')) {
    function ensureAdminsTable($conn)
    {
        $sql = "CREATE TABLE IF NOT EXISTS admins (
                    admin_id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(150) NOT NULL,
                    email VARCHAR(150) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
        mysqli_query($conn, $sql);
    }
}

if (!function_exists('getSemesterOptionsForYear')) {
    function getSemesterOptionsForYear($year)
    {
        $year = trim((string) $year);
        $map = [
            'First Year' => ['1', '2'],
            'Second Year' => ['3', '4'],
            'Third Year' => ['5', '6'],
            'Final Year' => ['7', '8'],
        ];

        return isset($map[$year]) ? $map[$year] : [];
    }
}

if (!function_exists('isValidSemesterForYear')) {
    function isValidSemesterForYear($year, $semester)
    {
        return in_array((string) $semester, getSemesterOptionsForYear($year), true);
    }
}

if (!function_exists('ensureFacultySchema')) {
    function ensureFacultySchema($conn)
    {
        ensureColumnExists($conn, 'faculty', 'signature', "VARCHAR(255) NULL AFTER photo");
    }
}

if (!function_exists('ensureComplaintWorkflowSchema')) {
    function ensureComplaintWorkflowSchema($conn)
    {
        $sql = "CREATE TABLE IF NOT EXISTS complaints (
            complaint_id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            status VARCHAR(100) NOT NULL DEFAULT 'Pending',
            remark TEXT NULL,
            completion_date DATE NULL,
            submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            deleted_by_student TINYINT(1) NOT NULL DEFAULT 0,
            deleted_by_warden TINYINT(1) NOT NULL DEFAULT 0
        )";
        mysqli_query($conn, $sql);
        ensureColumnExists($conn, 'complaints', 'status', "VARCHAR(100) NOT NULL DEFAULT 'Pending' AFTER description");
        ensureColumnExists($conn, 'complaints', 'remark', "TEXT NULL AFTER status");
        ensureColumnExists($conn, 'complaints', 'completion_date', "DATE NULL AFTER remark");

        // Soft-delete flags: each role can hide a record from their own view only
        ensureColumnExists($conn, 'complaints', 'deleted_by_student', "TINYINT(1) NOT NULL DEFAULT 0");
        ensureColumnExists($conn, 'complaints', 'deleted_by_warden',  "TINYINT(1) NOT NULL DEFAULT 0");

        ensureColumnExists($conn, 'gate_pass', 'deleted_by_student', "TINYINT(1) NOT NULL DEFAULT 0");
        ensureColumnExists($conn, 'gate_pass', 'deleted_by_hod',     "TINYINT(1) NOT NULL DEFAULT 0");
        ensureColumnExists($conn, 'gate_pass', 'deleted_by_warden',  "TINYINT(1) NOT NULL DEFAULT 0");

        // Security role flags
        ensureColumnExists($conn, 'gate_pass', 'security_status', "VARCHAR(20) NULL DEFAULT NULL");
        ensureColumnExists($conn, 'gate_pass', 'submitted_at',    "DATETIME NULL DEFAULT NULL");
        ensureColumnExists($conn, 'gate_pass', 'returned_at',     "DATETIME NULL DEFAULT NULL");
    }
}

if (!function_exists('normalizeFacultyRoleValue')) {
    function normalizeFacultyRoleValue($role)
    {
        $role = strtolower(trim((string) $role));
        if ($role === 'hod') {
            return 'HOD';
        }
        if ($role === 'warden') {
            return 'Warden';
        }
        if ($role === 'admin') {
            return 'Admin';
        }
        if ($role === 'security') {
            return 'Security';
        }
        return ucfirst($role);
    }
}

if (!function_exists('getFacultySignaturePath')) {
    function getFacultySignaturePath($conn, $role, $department = '')
    {
        ensureFacultySchema($conn);

        $dbRole = normalizeFacultyRoleValue($role);
        $department = trim((string) $department);

        if ($dbRole === 'HOD' && $department !== '') {
            $stmt = mysqli_prepare(
                $conn,
                "SELECT signature
                 FROM faculty
                 WHERE role = ? AND LOWER(TRIM(department)) = LOWER(TRIM(?)) AND signature IS NOT NULL AND TRIM(signature) <> ''
                 ORDER BY staff_id DESC
                 LIMIT 1"
            );
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ss", $dbRole, $department);
            }
        } else {
            $stmt = mysqli_prepare(
                $conn,
                "SELECT signature
                 FROM faculty
                 WHERE role = ? AND signature IS NOT NULL AND TRIM(signature) <> ''
                 ORDER BY staff_id DESC
                 LIMIT 1"
            );
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "s", $dbRole);
            }
        }

        if (!$stmt) {
            return '';
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        return $row && !empty($row['signature']) ? 'uploads/' . $row['signature'] : '';
    }
}
?>
