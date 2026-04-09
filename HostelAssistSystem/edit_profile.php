<?php
session_start();
include("config.php");
include("profile_common.php");
include_once("system_helpers.php");

$context = loadProfileContext($conn);
$isStudent = $context['isStudent'];
$table = $context['table'];
$idColumn = $context['idColumn'];
$userId = $context['userId'];
$backUrl = 'profile.php';
$row = $context['row'];
$message = '';
$messageType = 'success';
$departmentOptions = ['CSE', 'AIML', 'COMP', 'EXTC', 'CHEM', 'CIVIL', 'MECH'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_dir(__DIR__ . DIRECTORY_SEPARATOR . 'uploads')) {
        mkdir(__DIR__ . DIRECTORY_SEPARATOR . 'uploads', 0777, true);
    }

    $updates = [];
    $params = [];
    $types = '';

    if ($isStudent) {
        $department = trim((string) ($_POST['department'] ?? ''));
        $roomNumber = trim((string) ($_POST['room_number'] ?? ''));
        $year = trim((string) ($_POST['year'] ?? ''));
        $semester = trim((string) ($_POST['semester'] ?? ''));
        $mobile = trim((string) ($_POST['mobile'] ?? ''));

        $updates[] = "department = ?";
        $updates[] = "room_number = ?";
        $updates[] = "`year` = ?";
        $updates[] = "semester = ?";
        $updates[] = "mobile = ?";
        $params[] = $department;
        $params[] = $roomNumber;
        $params[] = $year;
        $params[] = $semester;
        $params[] = $mobile;
        $types .= 'sssss';
    } else {
        $department = trim((string) ($_POST['department'] ?? ''));
        $updates[] = "department = ?";
        $params[] = $department;
        $types .= 's';
    }

    if ($department === '') {
        $message = 'Please select a department.';
        $messageType = 'error';
    }

    if (
        $isStudent &&
        $messageType !== 'error' &&
        $mobile !== '' &&
        (
            $mobile === (string) ($row['parent_mobile1'] ?? '') ||
            ($mobile !== '' && $mobile === (string) ($row['parent_mobile2'] ?? ''))
        )
    ) {
        $message = 'Student mobile number must be different from both parent mobile numbers.';
        $messageType = 'error';
    }

    if ($isStudent && $messageType !== 'error' && !isValidSemesterForYear($year, $semester)) {
        $message = 'Selected semester does not match the selected year.';
        $messageType = 'error';
    }

    if (!empty($_FILES['photo']['name']) && isset($_FILES['photo']['tmp_name'])) {
        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($_FILES['photo']['name']));
        $photoName = time() . '_' . $safeName;
        $targetPath = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $photoName;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
            $updates[] = "photo = ?";
            $params[] = $photoName;
            $types .= 's';
        } else {
            $message = 'Profile image upload failed.';
            $messageType = 'error';
        }
    }

    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    $checkStmt = mysqli_prepare($conn, "SELECT password FROM `$table` WHERE `$idColumn` = ?");
    mysqli_stmt_bind_param($checkStmt, 'i', $userId);
    mysqli_stmt_execute($checkStmt);
    $passwordResult = mysqli_stmt_get_result($checkStmt);
    $passwordRow = $passwordResult ? mysqli_fetch_assoc($passwordResult) : null;
    mysqli_stmt_close($checkStmt);

    if ($newPassword !== '' || $confirmPassword !== '' || $currentPassword !== '') {
        if (!$passwordRow || $currentPassword !== (string) $passwordRow['password']) {
            $message = 'Current password is incorrect.';
            $messageType = 'error';
        } elseif ($newPassword === '' || $newPassword !== $confirmPassword) {
            $message = 'New password and confirm password must match.';
            $messageType = 'error';
        } else {
            $updates[] = "password = ?";
            $params[] = $newPassword;
            $types .= 's';
        }
    }

    if ($messageType !== 'error' && !empty($updates)) {
        $sql = "UPDATE `$table` SET " . implode(', ', $updates) . " WHERE `$idColumn` = ?";
        $stmt = mysqli_prepare($conn, $sql);
        $types .= 'i';
        $params[] = $userId;
        mysqli_stmt_bind_param($stmt, $types, ...$params);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['department'] = $department;
            header("Location: profile.php");
            exit;
        } else {
            $message = 'Unable to update profile right now.';
            $messageType = 'error';
        }

        mysqli_stmt_close($stmt);
    } elseif ($message === '') {
        $message = 'No changes were submitted.';
        $messageType = 'error';
    }

    $context = loadProfileContext($conn);
    $row = $context['row'];
}

$photoFile = !empty($row['photo']) ? 'uploads/' . $row['photo'] : 'images/default-profile.svg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Profile</title>
<style>
body {
    margin: 0;
    font-family: 'Segoe UI', Arial, sans-serif;
    background: linear-gradient(135deg, #edf7fa, #d8edf2);
    color: #1d3338;
}

.page {
    max-width: 820px;
    margin: 34px auto;
    padding: 0 18px 32px;
}

.topbar {
    margin-bottom: 18px;
}

.back-link {
    text-decoration: none;
    color: #057a8d;
    font-weight: 700;
}

.card {
    background: #ffffff;
    border-radius: 20px;
    padding: 28px;
    box-shadow: 0 14px 30px rgba(5, 122, 141, 0.10);
}

h1, h2 {
    margin-top: 0;
    color: #0f5f6b;
}

.message {
    margin-bottom: 16px;
    padding: 12px 14px;
    border-radius: 12px;
}

.message.success {
    background: #e9f9ee;
    color: #21683b;
}

.message.error {
    background: #fff1f1;
    color: #9d2b2b;
}

.grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.field {
    margin-bottom: 16px;
}

label {
    display: block;
    margin-bottom: 7px;
    font-weight: 600;
    color: #24464d;
}

input, select {
    width: 100%;
    padding: 12px;
    border-radius: 10px;
    border: 1px solid #cfe1e7;
    box-sizing: border-box;
    background: #fbfeff;
}

input[readonly] {
    background: #f3f7f8;
}

.password-wrap {
    position: relative;
}

.password-wrap input {
    padding-right: 46px;
}

.toggle-password {
    position: absolute;
    top: 44%;
    right: 12px;
    transform: translateY(-50%);
    border: none;
    background: transparent;
    color: #057a8d;
    font-size: 18px;
    cursor: pointer;
    width: auto;
    padding: 0;
}

.photo-row {
    display: flex;
    align-items: center;
    gap: 18px;
    margin-bottom: 20px;
}

.profile-photo {
    width: 92px;
    height: 92px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #d6edf2;
}

.actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 12px;
}

button, .cancel-link {
    padding: 12px 18px;
    border: none;
    border-radius: 10px;
    background: #057a8d;
    color: #fff;
    cursor: pointer;
    font-weight: 600;
    text-decoration: none;
}

.cancel-link {
    background: #6b7c82;
}

@media (max-width: 820px) {
    .grid {
        grid-template-columns: 1fr;
    }

    .photo-row {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>
</head>
<body>
<div class="page">
    <!-- <div class="topbar">
        <a class="back-link" href="<?php echo htmlspecialchars($backUrl); ?>">&#8592; Back to Profile</a>
    </div> -->

    <?php if ($message !== '') { ?>
        <div class="message <?php echo $messageType === 'error' ? 'error' : 'success'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php } ?>

    <div class="card">
        <h1>Edit Profile</h1>

        <form method="POST" enctype="multipart/form-data">
            <div class="photo-row">
                <img class="profile-photo" src="<?php echo htmlspecialchars($photoFile); ?>" alt="Profile Photo">
                <div class="field" style="flex:1; margin-bottom:0;">
                    <label>Profile Picture</label>
                    <input type="file" name="photo" accept="image/*">
                </div>
            </div>

            <div class="grid">
                <div class="field">
                    <label>Name</label>
                    <input type="text" value="<?php echo htmlspecialchars($row['name']); ?>" readonly>
                </div>

                <div class="field">
                    <label>Email</label>
                    <input type="email" value="<?php echo htmlspecialchars($row['email']); ?>" readonly>
                </div>
            </div>

            <?php if ($isStudent) { ?>
                <div class="grid">
                    <div class="field">
                        <label>Student Mobile</label>
                        <input type="tel" name="mobile" pattern="[0-9]{10}" maxlength="10" value="<?php echo htmlspecialchars((string) ($row['mobile'] ?? '')); ?>" required>
                    </div>

                    <div class="field">
                        <label>Parent Mobile 1</label>
                        <input type="text" value="<?php echo htmlspecialchars((string) ($row['parent_mobile1'] ?? '')); ?>" readonly>
                    </div>
                </div>

                <div class="grid">
                    <div class="field">
                        <label>Parent Mobile 2</label>
                        <input type="text" value="<?php echo htmlspecialchars((string) ($row['parent_mobile2'] ?? '')); ?>" readonly>
                    </div>
                    <div class="field">
                    </div>
                </div>
            <?php } ?>

            <div class="grid">
                <div class="field">
                    <label>Department</label>
                    <select name="department" required>
                        <option value="">Select Department</option>
                        <?php foreach ($departmentOptions as $departmentOption) { ?>
                            <option value="<?php echo htmlspecialchars($departmentOption); ?>" <?php echo ((string) ($row['department'] ?? '') === $departmentOption) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($departmentOption); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <?php if ($isStudent) { ?>
                    <div class="field">
                        <label>Room Number</label>
                        <input type="text" name="room_number" value="<?php echo htmlspecialchars((string) ($row['room_number'] ?? '')); ?>">
                    </div>
                <?php } else { ?>
                    <div class="field">
                        <label>Role</label>
                        <input type="text" value="<?php echo htmlspecialchars((string) ($row['role'] ?? '')); ?>" readonly>
                    </div>
                <?php } ?>
            </div>

            <?php if ($isStudent) { ?>
                <div class="grid">
                    <div class="field">
                        <label>Year</label>
                        <select name="year" id="yearSelect">
                            <option value="">Select Year</option>
                            <?php foreach (['First Year', 'Second Year', 'Third Year', 'Final Year'] as $yearOption) { ?>
                                <option value="<?php echo htmlspecialchars($yearOption); ?>" <?php echo (($row['year'] ?? '') === $yearOption) ? 'selected' : ''; ?>><?php echo htmlspecialchars($yearOption); ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="field">
                        <label>Semester</label>
                        <select name="semester" id="semesterSelect">
                            <option value="">Select Semester</option>
                            <?php foreach (getSemesterOptionsForYear((string) ($row['year'] ?? '')) as $semesterOption) { ?>
                                <option value="<?php echo htmlspecialchars($semesterOption); ?>" <?php echo ((string) ($row['semester'] ?? '') === (string) $semesterOption) ? 'selected' : ''; ?>><?php echo htmlspecialchars($semesterOption); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
            <?php } ?>

            <h2>Change Password</h2>
            <div class="grid">
                <div class="field">
                    <label>Current Password</label>
                    <div class="password-wrap">
                        <input type="password" name="current_password" id="currentPassword">
                        <button type="button" class="toggle-password" onclick="togglePassword('currentPassword', this)" aria-label="Show password">&#128065;</button>
                    </div>
                </div>

                <div class="field">
                    <label>New Password</label>
                    <div class="password-wrap">
                        <input type="password" name="new_password" id="newPassword">
                        <button type="button" class="toggle-password" onclick="togglePassword('newPassword', this)" aria-label="Show password">&#128065;</button>
                    </div>
                </div>
            </div>

            <div class="field">
                <label>Confirm New Password</label>
                <div class="password-wrap">
                    <input type="password" name="confirm_password" id="confirmNewPassword">
                    <button type="button" class="toggle-password" onclick="togglePassword('confirmNewPassword', this)" aria-label="Show password">&#128065;</button>
                </div>
            </div>

            <div class="actions">
                <button type="submit">Save Changes</button>
                <a class="cancel-link" href="profile.php">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php if ($isStudent) { ?>
<script>
const semesterMap = {
    "First Year": ["1", "2"],
    "Second Year": ["3", "4"],
    "Third Year": ["5", "6"],
    "Final Year": ["7", "8"]
};

const yearSelect = document.getElementById('yearSelect');
const semesterSelect = document.getElementById('semesterSelect');
const currentSemester = <?php echo json_encode((string) ($row['semester'] ?? '')); ?>;

function updateSemesterOptions(keepCurrent) {
    const selectedYear = yearSelect.value;
    const options = semesterMap[selectedYear] || [];
    const targetValue = keepCurrent ? currentSemester : '';
    semesterSelect.innerHTML = '<option value="">Select Semester</option>';

    options.forEach(function(optionValue) {
        const option = document.createElement('option');
        option.value = optionValue;
        option.textContent = optionValue;
        if (optionValue === targetValue) {
            option.selected = true;
        }
        semesterSelect.appendChild(option);
    });

    if (!keepCurrent) {
        semesterSelect.value = '';
    }
}

yearSelect.addEventListener('change', function() {
    updateSemesterOptions(false);
});

updateSemesterOptions(true);
</script>
<?php } ?>
<script>
function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    if (!input) {
        return;
    }

    if (input.type === 'password') {
        input.type = 'text';
    } else {
        input.type = 'password';
    }
}
</script>
</body>
</html>
