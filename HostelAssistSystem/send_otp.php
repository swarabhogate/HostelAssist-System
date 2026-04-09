<?php
session_start();
include("config.php");
include_once("system_helpers.php");

ensureAdminsTable($conn);
ensureFacultySchema($conn);

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    exit;
}

$userType = strtolower(trim((string) ($_POST['user_type'] ?? '')));
$email = normalizeEmailAddress($_POST['email'] ?? '');

if ($userType === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Please enter a valid email address."]);
    exit;
}

if ($userType === 'student') {
    $stmt = mysqli_prepare($conn, "SELECT student_id, name FROM students WHERE LOWER(TRIM(email)) = ?");
} elseif ($userType === 'admin') {
    $stmt = mysqli_prepare($conn, "SELECT admin_id, name FROM admins WHERE LOWER(TRIM(email)) = ?");
} elseif ($userType === 'faculty' || $userType === 'security') {
    $stmt = mysqli_prepare($conn, "SELECT staff_id, name, role FROM faculty WHERE LOWER(TRIM(email)) = ?");
} else {
    echo json_encode(["success" => false, "message" => "Invalid account type selected."]);
    exit;
}

if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Unable to prepare account lookup."]);
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

if (!$user) {
    echo json_encode(["success" => false, "message" => "Email not found."]);
    exit;
}

if ($userType === 'faculty' || $userType === 'security') {
    $dbRole = strtolower(trim((string) ($user['role'] ?? '')));

    if ($userType === 'security' && $dbRole !== 'security') {
        echo json_encode(["success" => false, "message" => "This email is not registered as a Security account."]);
        exit;
    }

    if ($userType === 'faculty' && $dbRole === 'security') {
        echo json_encode(["success" => false, "message" => "This email belongs to a Security account."]);
        exit;
    }
}

$otp = random_int(100000, 999999);

$_SESSION['reset_email'] = $email;
$_SESSION['reset_role'] = $userType;
$_SESSION['reset_otp'] = $otp;
$_SESSION['otp_expiry'] = time() + 300;

require 'PHPMailer-6.8.1/src/Exception.php';
require 'PHPMailer-6.8.1/src/PHPMailer.php';
require 'PHPMailer-6.8.1/src/SMTP.php';

$mail = new \PHPMailer\PHPMailer\PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = MAIL_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = MAIL_USERNAME;
    $mail->Password = MAIL_PASSWORD;
    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = MAIL_PORT;
    $mail->CharSet = 'UTF-8';
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ],
    ];

    $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
    $mail->addAddress($email, (string) ($user['name'] ?? 'User'));
    $mail->isHTML(true);
    $mail->Subject = 'OTP for Password Reset';
    $mail->Body = "
        <h3>Password Reset OTP</h3>
        <p>Hello " . htmlspecialchars((string) ($user['name'] ?? 'User')) . ",</p>
        <p>Your OTP is:</p>
        <h1 style='color:#0f5f6b;'>$otp</h1>
        <p>This OTP is valid for 5 minutes.</p>
        <p>Please do not share it with anyone.</p>
    ";
    $mail->AltBody = "Your OTP is: $otp";
    $mail->send();

    echo json_encode([
        "success" => true,
        "message" => "OTP sent successfully"
    ]);
} catch (\PHPMailer\PHPMailer\Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Mailer Error: " . $mail->ErrorInfo
    ]);
}
?>
