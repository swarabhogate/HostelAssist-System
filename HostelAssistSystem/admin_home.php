<?php
session_start();
include("config.php");
include_once("system_helpers.php");

ensureAdminsTable($conn);
ensureFacultySchema($conn);

if (!isset($_SESSION['role']) || strtolower(trim((string) $_SESSION['role'])) !== 'admin') {
    header("Location: login.html");
    exit;
}

$message = '';
$messageType = 'success';
$departmentOptions = ['AIML', 'COMP', 'EXTC', 'CHEM', 'CIVIL', 'MECH'];

if (!is_dir(__DIR__ . DIRECTORY_SEPARATOR . 'uploads')) {
    mkdir(__DIR__ . DIRECTORY_SEPARATOR . 'uploads', 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));

    if ($action === 'add_faculty') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = normalizeEmailAddress($_POST['email'] ?? '');
        $mobile = trim((string) ($_POST['mobile'] ?? ''));
        $password = trim((string) ($_POST['password'] ?? ''));
        $role = trim((string) ($_POST['role'] ?? ''));
        $department = trim((string) ($_POST['department'] ?? ''));
        $signatureName = '';

        if ($name === '' || $email === '' || $mobile === '' || $password === '' || $role === '' || $department === '') {
            $message = 'Please fill all faculty fields.';
            $messageType = 'error';
        } elseif (!in_array($role, ['HOD', 'Warden', 'Security'], true)) {
            $message = 'Invalid faculty role selected.';
            $messageType = 'error';
        } else {
            $checkStmt = mysqli_prepare($conn, "SELECT staff_id FROM faculty WHERE email = ?");
            if ($checkStmt) {
                mysqli_stmt_bind_param($checkStmt, "s", $email);
                mysqli_stmt_execute($checkStmt);
                $existingResult = mysqli_stmt_get_result($checkStmt);
                if ($existingResult && mysqli_num_rows($existingResult) > 0) {
                    $message = 'Faculty email already exists.';
                    $messageType = 'error';
                }
                mysqli_stmt_close($checkStmt);
            }
        }

        if ($messageType !== 'error' && !empty($_FILES['signature']['name']) && isset($_FILES['signature']['tmp_name'])) {
            $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($_FILES['signature']['name']));
            $signatureName = time() . '_' . $safeName;
            $targetPath = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $signatureName;

            if (!move_uploaded_file($_FILES['signature']['tmp_name'], $targetPath)) {
                $message = 'Signature upload failed.';
                $messageType = 'error';
            }
        }

        if ($messageType !== 'error' && $role === 'HOD' && $signatureName === '') {
            $message = 'Please upload the HOD signature for this department.';
            $messageType = 'error';
        }

        if ($messageType !== 'error') {
            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO faculty (name, email, mobile, password, role, department, signature) VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "sssssss", $name, $email, $mobile, $password, $role, $department, $signatureName);
                if (mysqli_stmt_execute($stmt)) {
                    $message = 'Faculty account created successfully.';
                    $messageType = 'success';
                } else {
                    $message = 'Unable to create faculty account.';
                    $messageType = 'error';
                }
                mysqli_stmt_close($stmt);
            } else {
                $message = 'Unable to prepare faculty insert.';
                $messageType = 'error';
            }
        }
    } elseif ($action === 'delete_faculty') {
        $staffId = (int) ($_POST['staff_id'] ?? 0);

        if ($staffId <= 0) {
            $message = 'Invalid faculty account selected.';
            $messageType = 'error';
        } else {
            $selectStmt = mysqli_prepare($conn, "SELECT signature FROM faculty WHERE staff_id = ? AND role IN ('HOD', 'Warden', 'Security')");
            if ($selectStmt) {
                mysqli_stmt_bind_param($selectStmt, "i", $staffId);
                mysqli_stmt_execute($selectStmt);
                $result = mysqli_stmt_get_result($selectStmt);
                $facultyRow = $result ? mysqli_fetch_assoc($result) : null;
                mysqli_stmt_close($selectStmt);
            } else {
                $facultyRow = null;
            }

            if (!$facultyRow) {
                $message = 'Faculty account not found.';
                $messageType = 'error';
            } else {
                $deleteStmt = mysqli_prepare($conn, "DELETE FROM faculty WHERE staff_id = ? AND role IN ('HOD', 'Warden', 'Security')");
                if ($deleteStmt) {
                    mysqli_stmt_bind_param($deleteStmt, "i", $staffId);
                    if (mysqli_stmt_execute($deleteStmt)) {
                        if (!empty($facultyRow['signature'])) {
                            $signaturePath = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $facultyRow['signature'];
                            if (is_file($signaturePath)) {
                                unlink($signaturePath);
                            }
                        }
                        $message = 'Faculty account deleted successfully.';
                        $messageType = 'success';
                    } else {
                        $message = 'Unable to delete faculty account.';
                        $messageType = 'error';
                    }
                    mysqli_stmt_close($deleteStmt);
                } else {
                    $message = 'Unable to prepare faculty delete.';
                    $messageType = 'error';
                }
            }
        }
    }
}

$facultyRows = [];
$facultyResult = mysqli_query(
    $conn,
    "SELECT staff_id, name, email, mobile, role, department, signature
     FROM faculty
     WHERE role IN ('HOD', 'Warden', 'Security')
     ORDER BY role ASC, department ASC, staff_id DESC"
);
if ($facultyResult) {
    $facultyRows = mysqli_fetch_all($facultyResult, MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background:
                radial-gradient(circle at top right, rgba(255, 221, 87, 0.28), transparent 28%),
                radial-gradient(circle at top left, rgba(23, 122, 141, 0.26), transparent 30%),
                linear-gradient(135deg, #f7fbfc, #d5edf1);
            color: #1d3338;
        }

        .page {
            max-width: 1180px;
            margin: 28px auto;
            padding: 0 18px 28px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
            gap: 12px;
            background: linear-gradient(135deg, #0f5f6b, #177e89);
            color: #fff;
            border-radius: 24px;
            padding: 24px 26px;
            box-shadow: 0 18px 38px rgba(15, 95, 107, 0.20);
        }

        .topbar h1 {
            margin: 0;
            color: #fff;
        }

        .topbar p {
            margin: 8px 0 0;
            color: rgba(255, 255, 255, 0.82);
        }

        .logout-link {
            text-decoration: none;
            background: #fff;
            color: #0f5f6b;
            padding: 10px 16px;
            border-radius: 999px;
            font-weight: 700;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 18px;
        }

        .card {
            background: rgba(255, 255, 255, 0.92);
            border-radius: 24px;
            padding: 24px;
            box-shadow: 0 18px 36px rgba(5, 122, 141, 0.12);
            border: 1px solid rgba(15, 95, 107, 0.08);
            backdrop-filter: blur(6px);
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

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .field {
            margin-bottom: 14px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
        }

        input,
        select {
            width: 100%;
            padding: 11px 12px;
            border-radius: 10px;
            border: 1px solid #cfe1e7;
            box-sizing: border-box;
        }

        button {
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #057a8d, #0f5f6b);
            color: #fff;
            padding: 12px 16px;
            cursor: pointer;
            font-weight: 600;
        }

        button:hover {
            transform: translateY(-1px);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            text-align: left;
            padding: 12px 10px;
            border-bottom: 1px solid #e1ecef;
            vertical-align: top;
        }

        .delete-btn {
            background: #c0392b;
        }

        .signature-preview {
            max-width: 110px;
            max-height: 44px;
            object-fit: contain;
        }

        .hint {
            color: #5b676c;
            font-size: 13px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .badge.hod {
            background: #fff1c7;
            color: #8a6200;
        }

        .badge.warden {
            background: #dff5fb;
            color: #0b6976;
        }

        @media (max-width: 900px) {

            .grid,
            .form-grid {
                grid-template-columns: 1fr;
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>
    <div class="page">
        <div class="topbar">
            <div>
                <h1>&#128187; Admin Dashboard</h1>
                <!-- <p>Create and manage HOD and Warden accounts from one place.</p> -->
            </div>
            <a class="logout-link" href="logout.php"
                onclick="return confirm('Are you sure you want to logout?');">Logout</a>
        </div>

        <?php if (isset($_SESSION['login_message'])): ?>
            <div id="loginMessage" class="message success"
                style="margin: 15px auto; max-width: 1200px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; animation: slideDown 0.3s ease-in;">
                <span><?php echo htmlspecialchars($_SESSION['login_message']); ?></span>
                <button type="button"
                    style="background: none; border: none; font-size: 18px; cursor: pointer; color: inherit;"
                    onclick="document.getElementById('loginMessage').remove();">&times;</button>
            </div>
            <style>
                @keyframes slideDown {
                    from {
                        transform: translateY(-20px);
                        opacity: 0;
                    }

                    to {
                        transform: translateY(0);
                        opacity: 1;
                    }
                }
            </style>
            <script>
                setTimeout(function () {
                    const msg = document.getElementById('loginMessage');
                    if (msg) {
                        msg.style.animation = 'slideUp 0.3s ease-out forwards';
                        setTimeout(function () { msg.remove(); }, 300);
                    }
                }, 10000);
                const style = document.createElement('style');
                style.textContent = '@keyframes slideUp { from { transform: translateY(0); opacity: 1; } to { transform: translateY(-20px); opacity: 0; } }';
                document.head.appendChild(style);
            </script>
            <?php unset($_SESSION['login_message']); endif; ?>

        <?php if ($message !== '') { ?>
            <div class="message <?php echo $messageType === 'error' ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php } ?>

        <div class="grid">
            <div class="card">
                <h2>&#10010; Add HOD / Warden / Security</h2>
                <!-- <p class="hint">Faculty accounts are created only from this admin page. Upload a different signature for each department HOD.</p> -->
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_faculty">

                    <div class="field">
                        <label>Full Name</label>
                        <input type="text" name="name" required>
                    </div>

                    <div class="form-grid">
                        <div class="field">
                            <label>Email</label>
                            <input type="email" name="email" id="facultyEmail" style="text-transform: lowercase;"
                                required>
                        </div>
                        <div class="field">
                            <label>Mobile</label>
                            <input type="tel" name="mobile" pattern="[0-9]{10}" maxlength="10" required>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="field">
                            <label>Department</label>
                            <select name="department" id="facultyDepartment" required>
                                <option value="">Select Department</option>
                                <option value="None">None (For Security)</option>
                                <?php foreach ($departmentOptions as $departmentOption) { ?>
                                    <option value="<?php echo htmlspecialchars($departmentOption); ?>">
                                        <?php echo htmlspecialchars($departmentOption); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="field">
                            <label>Role</label>
                            <select name="role" id="facultyRole" required>
                                <option value="">Select Role</option>
                                <option value="HOD">HOD</option>
                                <option value="Warden">Warden</option>
                                <option value="Security">Security</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="field">
                            <label>Password</label>
                            <input type="text" name="password" required>
                        </div>
                        <div class="field">
                            <label>Signature Image</label>
                            <input type="file" name="signature" id="signatureInput" accept="image/*">
                        </div>
                    </div>

                    <button type="submit">Create Faculty Account</button>
                </form>
            </div>

            <div class="card">
                <h2>&#128221; Faculty Accounts</h2>
                <?php if (!empty($facultyRows)) { ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Department</th>
                                <th>Signature</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($facultyRows as $facultyRow) { ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars((string) $facultyRow['name']); ?><br>
                                        <span class="hint"><?php echo htmlspecialchars((string) $facultyRow['email']); ?></span>
                                    </td>
                                    <td>
                                        <span
                                            class="badge <?php echo strtolower((string) $facultyRow['role']) === 'hod' ? 'hod' : (strtolower((string) $facultyRow['role']) === 'warden' ? 'warden' : 'security'); ?>">
                                            <?php echo strtolower((string) $facultyRow['role']) === 'hod' ? '&#9997;' : (strtolower((string) $facultyRow['role']) === 'warden' ? '&#127969;' : '&#128110;'); ?>
                                            <?php echo htmlspecialchars((string) $facultyRow['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars((string) $facultyRow['department']); ?></td>
                                    <td>
                                        <?php if (!empty($facultyRow['signature'])) { ?>
                                            <img class="signature-preview"
                                                src="uploads/<?php echo htmlspecialchars((string) $facultyRow['signature']); ?>"
                                                alt="Signature">
                                        <?php } else { ?>
                                            <span class="hint">No signature</span>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('Delete this faculty account?');">
                                            <input type="hidden" name="action" value="delete_faculty">
                                            <input type="hidden" name="staff_id"
                                                value="<?php echo (int) $facultyRow['staff_id']; ?>">
                                            <button type="submit" class="delete-btn">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } else { ?>
                    <p>No HOD, Warden or Security accounts found.</p>
                <?php } ?>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('facultyEmail').addEventListener('input', function () {
            this.value = this.value.toLowerCase();
        });

        document.querySelector('form').addEventListener('submit', function (event) {
            const role = document.getElementById('facultyRole').value;
            const signatureValue = document.getElementById('signatureInput').value;
            const deptValue = document.getElementById('facultyDepartment').value;
            if (role === 'HOD' && signatureValue.trim() === '') {
                alert('Please upload the HOD signature for this department.');
                event.preventDefault();
            }
            if (role === 'Security' && deptValue !== 'None') {
                alert('Please select "None" for department when creating a Security account.');
                event.preventDefault();
            }
        });
    </script>
</body>

</html>