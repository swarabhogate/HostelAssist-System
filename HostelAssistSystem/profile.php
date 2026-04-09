<?php
session_start();
include("config.php");
include("profile_common.php");

$context = loadProfileContext($conn);
$isStudent = $context['isStudent'];
$backUrl = $context['backUrl'];
$row = $context['row'];
$photoFile = $context['photoFile'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile</title>
<style>
body {
    margin: 0;
    font-family: 'Segoe UI', Arial, sans-serif;
    background: linear-gradient(135deg, #edf7fa, #d8edf2);
    color: #1d3338;
}

.page {
    max-width: 760px;
    margin: 34px auto;
    padding: 0 18px 32px;
}

.back-link {
    position: absolute;
    top: 22px;
    left: 22px;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: #ffffff;
    font-size: 22px;
    font-weight: 700;
    background: #057a8d;
    box-shadow: 0 8px 18px rgba(5, 122, 141, 0.18);
}

.card {
    position: relative;
    background: #ffffff;
    border-radius: 20px;
    padding: 28px;
    box-shadow: 0 14px 30px rgba(5, 122, 141, 0.10);
}

.profile-photo {
    width: 130px;
    height: 130px;
    border-radius: 50%;
    object-fit: cover;
    display: block;
    margin: 0 auto 16px;
    border: 4px solid #d6edf2;
}

h1 {
    margin-top: 0;
    color: #0f5f6b;
    text-align: center;
    margin-bottom: 8px;
}

.detail-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 12px;
}

.detail-box {
    padding: 14px 16px;
    border-radius: 14px;
    background: #f8fcfd;
    border: 1px solid #dbecef;
}

.detail-box strong {
    display: block;
    margin-bottom: 6px;
    color: #34545b;
    font-size: 14px;
}

.update-btn {
    display: inline-block;
    margin-top: 22px;
    padding: 12px 18px;
    border-radius: 12px;
    background: #057a8d;
    color: #fff;
    text-decoration: none;
    font-weight: 600;
}

@media (max-width: 820px) {
    .page {
        max-width: 100%;
    }
}
</style>
</head>
<body>
<div class="page">
    <div class="card">
        <a class="back-link" href="<?php echo htmlspecialchars($backUrl); ?>" aria-label="Go back">&#8592;</a>
        <img class="profile-photo" src="<?php echo htmlspecialchars($photoFile); ?>" alt="Profile Photo">
        <h1><?php echo htmlspecialchars($row['name']); ?></h1>

        <div class="detail-grid">
            <div class="detail-box">
                <strong>Email</strong>
                <span><?php echo htmlspecialchars($row['email']); ?></span>
            </div>

            <div class="detail-box">
                <strong>Mobile</strong>
                <span><?php echo htmlspecialchars((string) ($row['mobile'] ?? 'Not added')); ?></span>
            </div>

            <div class="detail-box">
                <strong>Department</strong>
                <span><?php echo htmlspecialchars($row['department']); ?></span>
            </div>

            <?php if ($isStudent) { ?>
                <div class="detail-box">
                    <strong>Year</strong>
                    <span><?php echo htmlspecialchars((string) ($row['year'] ?? '')); ?></span>
                </div>

                <div class="detail-box">
                    <strong>Semester</strong>
                    <span><?php echo htmlspecialchars((string) ($row['semester'] ?? '')); ?></span>
                </div>

                <div class="detail-box">
                    <strong>Room Number</strong>
                    <span><?php echo htmlspecialchars((string) ($row['room_number'] ?? '')); ?></span>
                </div>
            <?php } else { ?>
                <div class="detail-box">
                    <strong>Role</strong>
                    <span><?php echo htmlspecialchars((string) ($row['role'] ?? '')); ?></span>
                </div>
            <?php } ?>
        </div>

        <a class="update-btn" href="edit_profile.php">Update Profile</a>
    </div>
</div>
</body>
</html>
