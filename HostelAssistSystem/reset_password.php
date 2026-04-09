<?php
session_start();
include("config.php");
include_once("system_helpers.php");

if (!isset($_SESSION['reset_email']) || !isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
    header("Location: login.html");
    exit;
}

$error = '';
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $newPass = trim((string) ($_POST['new_password'] ?? ''));
    $confirmPass = trim((string) ($_POST['confirm_password'] ?? ''));
    $email = normalizeEmailAddress($_SESSION['reset_email']);
    $role = strtolower(trim((string) ($_SESSION['reset_role'] ?? '')));

    if ($newPass === '' || $confirmPass === '') {
        $error = 'Please enter and confirm your new password.';
    } elseif ($newPass !== $confirmPass) {
        $error = 'Passwords do not match!';
    } elseif (strlen($newPass) < 4) {
        $error = 'Password must be at least 4 characters long.';
    } else {
        if ($role === 'student') {
            $stmt = mysqli_prepare($conn, "UPDATE students SET password = ? WHERE LOWER(TRIM(email)) = ?");
        } elseif ($role === 'admin') {
            $stmt = mysqli_prepare($conn, "UPDATE admins SET password = ? WHERE LOWER(TRIM(email)) = ?");
        } elseif ($role === 'faculty' || $role === 'security') {
            $stmt = mysqli_prepare($conn, "UPDATE faculty SET password = ? WHERE LOWER(TRIM(email)) = ?");
        } else {
            $stmt = false;
        }

        if (!$stmt) {
            $error = 'Unable to update password right now. Please try again.';
        } else {
            mysqli_stmt_bind_param($stmt, "ss", $newPass, $email);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                session_unset();
                session_destroy();
                $success = true;
            } else {
                $error = 'Database error. Please try again.';
                mysqli_stmt_close($stmt);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Set New Password - HostelAssist</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <style>
        .login-card {
            background: rgba(255, 255, 255, 0.96);
            border-radius: 18px;
            padding: 35px 30px;
            box-shadow: 0 15px 40px rgba(5, 122, 141, .15);
            max-width: 480px;
            margin: auto;
            border: 1px solid rgba(15, 95, 107, 0.1);
        }
    </style>
</head>
<body style="background: linear-gradient(135deg, #f7fbfc, #d5edf1); min-height: 100vh; display: flex; align-items: center; justify-content: center;">

<div class="container">
    <div class="row align-items-center">
        <div class="col-12">
            <div class="login-card">
                <?php if ($success): ?>
                    <div class="text-center">
                        <div style="font-size: 50px; color: #28a745;">&#10004;</div>
                        <h3 style="color: #0f5f6b; font-weight: 700; margin-bottom: 20px;">Password Reset!</h3>
                        <p style="color: #666; font-size: 15px;">Your password has been successfully updated.</p>
                        <a href="login.html" class="btn btn-block mt-4" style="background: linear-gradient(135deg, #28a745, #1e7e34); color: white; height: 50px; border-radius: 12px; font-weight: 600; font-size: 16px; padding-top:12px;">Login Now</a>
                    </div>
                <?php else: ?>
                <div class="text-center mb-4">
                    <h3 style="color: #0f5f6b; font-weight: 700; margin-bottom: 5px;">Set New Password</h3>
                    <p style="color: #666; font-size: 14px;">Please enter your new password below.</p>
                </div>

                <?php if($error): ?>
                <div style="background:#fff1f1; color:#9d2b2b; padding:10px; border-radius:8px; margin-bottom:15px; font-size:14px; text-align:center;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <form method="post">
                    <div class="form-group mb-3">
                        <label>New Password</label>
                        <input type="password" name="new_password" class="form-control" style="border-radius: 10px; height: 48px;" required>
                    </div>

                    <div class="form-group mb-4">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" style="border-radius: 10px; height: 48px;" required>
                    </div>

                    <button type="submit" class="btn btn-block" style="background: linear-gradient(135deg, #17a2b8, #0f5f6b); color: white; height: 50px; border-radius: 12px; font-weight: 600; font-size: 16px;">
                        Update Password
                    </button>
                </form>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>
</body>
</html>
