<?php
session_start();
if (!isset($_SESSION['reset_email'])) {
    header("Location: login.html");
    exit;
}

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $enteredOtp = trim($_POST['otp']);
    
    if (time() > $_SESSION['otp_expiry']) {
        $error = 'OTP has expired. Please request a new one.';
        session_unset();
        session_destroy();
    } elseif ($enteredOtp == $_SESSION['reset_otp']) {
        $_SESSION['otp_verified'] = true;
        header("Location: reset_password.php");
        exit;
    } else {
        $error = 'Invalid OTP. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Verify OTP - HostelAssist</title>
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
                <div class="text-center mb-4">
                    <h3 style="color: #0f5f6b; font-weight: 700; margin-bottom: 5px;">Verify OTP</h3>
                    <p style="color: #666; font-size: 14px;">We sent a 6-digit code to <strong><?php echo htmlspecialchars($_SESSION['reset_email']); ?></strong></p>
                </div>

                <?php if($error): ?>
                <div style="background:#fff1f1; color:#9d2b2b; padding:10px; border-radius:8px; margin-bottom:15px; font-size:14px; text-align:center;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <form method="post">
                    <div class="form-group mb-4">
                        <label>6-Digit OTP</label>
                        <input type="text" name="otp" class="form-control text-center" style="border-radius: 10px; height: 55px; font-size: 24px; letter-spacing: 8px; font-weight: bold;" maxlength="6" pattern="\d{6}" placeholder="------" required autofocus>
                    </div>

                    <button type="submit" class="btn btn-block" style="background: linear-gradient(135deg, #17a2b8, #0f5f6b); color: white; height: 50px; border-radius: 12px; font-weight: 600; font-size: 16px;">
                        Verify Code
                    </button>
                </form>

            </div>
        </div>
    </div>
</div>
</body>
</html>
