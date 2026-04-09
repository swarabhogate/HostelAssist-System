<?php
session_start();
include("config.php");
include_once("system_helpers.php");

if (!isset($_SESSION['role']) || strtolower(trim((string) $_SESSION['role'])) !== 'admin') {
    echo "<script>alert('Faculty accounts can only be created by admin.'); window.location='login.html';</script>";
    exit;
}

ensureFacultySchema($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_home.php");
    exit;
}

$name = trim((string) ($_POST['name'] ?? ''));
$email = normalizeEmailAddress($_POST['email'] ?? '');
$mobile = trim((string) ($_POST['mobile'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$role = trim((string) ($_POST['role'] ?? ''));
$department = trim((string) ($_POST['department'] ?? ''));

if ($name === '' || $email === '' || $password === '' || $role === '' || $department === '') {
    echo "<script>alert('Please fill all fields.'); window.location='admin_home.php';</script>";
    exit;
}

if (!in_array($role, ['HOD', 'Warden'], true)) {
    echo "<script>alert('Invalid faculty role selected.'); window.location='admin_home.php';</script>";
    exit;
}

$checkStmt = mysqli_prepare($conn, "SELECT staff_id FROM faculty WHERE email = ?");
if ($checkStmt) {
    mysqli_stmt_bind_param($checkStmt, "s", $email);
    mysqli_stmt_execute($checkStmt);
    $existingResult = mysqli_stmt_get_result($checkStmt);
    if ($existingResult && mysqli_num_rows($existingResult) > 0) {
        mysqli_stmt_close($checkStmt);
        echo "<script>alert('Faculty email already exists.'); window.location='admin_home.php';</script>";
        exit;
    }
    mysqli_stmt_close($checkStmt);
}

$stmt = mysqli_prepare($conn, "INSERT INTO faculty (name, email, mobile, password, role, department) VALUES (?, ?, ?, ?, ?, ?)");

if (!$stmt) {
    echo "Error: " . mysqli_error($conn);
    exit;
}

mysqli_stmt_bind_param($stmt, "ssssss", $name, $email, $mobile, $password, $role, $department);

if (mysqli_stmt_execute($stmt)) {
    echo "<script>alert('Faculty registration successful'); window.location='admin_home.php';</script>";
} else {
    echo "Error: " . mysqli_error($conn);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
