<?php
session_start();
include('config.php');
include_once('system_helpers.php');

ensureAdminsTable($conn);
ensureFacultySchema($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = normalizeEmailAddress($_POST['email'] ?? '');
    $password = $_POST['password'];
    $user_type = strtolower(trim((string) ($_POST['user_type'] ?? 'student')));

    // ===== VALIDATION =====
    if (empty($email) || empty($password)) {
        echo "<script>alert('Enter email & password'); window.location='login.html';</script>";
        exit;
    }

    // ===== STUDENT EMAIL VALIDATION =====
    if ($user_type === 'student') {
        if (!preg_match("/^(dse|en)[0-9]+@git-india\.edu\.in$/", $email)) {
            echo "<script>alert('Use valid college email'); window.location='login.html';</script>";
            exit;
        }
    }

    // ================= STUDENT LOGIN =================
    if ($user_type === 'student') {

        $sql = "SELECT student_id, name, password FROM students WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {

            mysqli_stmt_bind_result($stmt, $id, $name, $db_password);
            mysqli_stmt_fetch($stmt);

            if ($password === $db_password) {

                $_SESSION['user_id'] = $id;
                $_SESSION['name'] = $name;
                $_SESSION['role'] = 'student';
                $_SESSION['login_message'] = 'Welcome! You are logged into the Student Dashboard';

                header("Location: Student_home.php");
                exit;

            } else {
                echo "<script>alert('Wrong password'); window.location='login.html';</script>";
                exit;
            }
        } else {
            echo "<script>alert('Student not found'); window.location='login.html';</script>";
            exit;
        }

        mysqli_stmt_close($stmt);
    }

    // ================= FACULTY & SECURITY LOGIN =================
    if ($user_type === 'faculty' || $user_type === 'security') {

        $sql = "SELECT staff_id, name, password, role, department FROM faculty WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {

            mysqli_stmt_bind_result($stmt, $id, $name, $db_password, $role, $department);
            mysqli_stmt_fetch($stmt);

            if ($password === $db_password) {

                $_SESSION['user_id'] = $id;
                $_SESSION['name'] = $name;
                $_SESSION['role'] = $role;
                $_SESSION['department'] = $department;
                $_SESSION['login_popup'] = true;

                // 🔥 Role-based redirect and message
                if ($role === 'HOD') {
                    if ($user_type !== 'faculty') {
                        echo "<script>alert('Please use Faculty login'); window.location='login.html';</script>";
                        exit;
                    }
                    $_SESSION['login_message'] = 'Welcome! You are logged into the HOD Dashboard';
                    header("Location: hod_home.php");
                } elseif ($role === 'Warden') {
                    if ($user_type !== 'faculty') {
                        echo "<script>alert('Please use Faculty login'); window.location='login.html';</script>";
                        exit;
                    }
                    $_SESSION['login_message'] = 'Welcome! You are logged into the Warden Dashboard';
                    header("Location: warden_home.php");
                } elseif ($role === 'Admin') {
                    if ($user_type !== 'admin') {
                        echo "<script>alert('Please use Admin login'); window.location='login.html';</script>";
                        exit;
                    }
                    $_SESSION['login_message'] = 'Welcome! You are logged into the Admin Dashboard';
                    header("Location: admin_home.php");
                } elseif ($role === 'Security') {
                    if ($user_type !== 'security') {
                        echo "<script>alert('Please use Security login'); window.location='login.html';</script>";
                        exit;
                    }
                    $_SESSION['login_message'] = 'Welcome! You are logged into the Security Dashboard';
                    header("Location: security_home.php");
                } else {
                    if ($user_type !== 'faculty') {
                        echo "<script>alert('Please use Faculty login'); window.location='login.html';</script>";
                        exit;
                    }
                    $_SESSION['login_message'] = 'Welcome! You are logged into the Faculty Dashboard';
                    header("Location: home.php");
                }
                exit;

            } else {
                echo "<script>alert('Wrong password'); window.location='login.html';</script>";
                exit;
            }
        } else {
            echo "<script>alert('Faculty not found'); window.location='login.html';</script>";
            exit;
        }

        mysqli_stmt_close($stmt);
    }

    // ================= ADMIN LOGIN =================
    if ($user_type === 'admin') {
        $sql = "SELECT admin_id, name, password FROM admins WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            mysqli_stmt_bind_result($stmt, $id, $name, $db_password);
            mysqli_stmt_fetch($stmt);

            if ($password === $db_password) {
                $_SESSION['user_id'] = $id;
                $_SESSION['name'] = $name;
                $_SESSION['role'] = 'admin';
                $_SESSION['login_message'] = 'Welcome! You are logged into the Admin Dashboard';

                header("Location: admin_home.php");
                exit;
            } else {
                echo "<script>alert('Wrong password'); window.location='login.html';</script>";
                exit;
            }
        } else {
            echo "<script>alert('Admin not found'); window.location='login.html';</script>";
            exit;
        }

        mysqli_stmt_close($stmt);
    }

    // ===== FALLBACK =====
    echo "<script>alert('Invalid login'); window.location='login.html';</script>";
    exit;
}
?>
