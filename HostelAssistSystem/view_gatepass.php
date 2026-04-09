<?php
session_start();
include("config.php");
include_once("system_helpers.php");

ensureFacultySchema($conn);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
$userDepartment = isset($_SESSION['department']) ? trim((string) $_SESSION['department']) : '';
$flashMessage = isset($_SESSION['gatepass_flash']) ? $_SESSION['gatepass_flash'] : '';
unset($_SESSION['gatepass_flash']);

$sql = "SELECT gp.*, 
               s.name, s.mobile, s.parent_mobile1, s.parent_mobile2,
               s.department, s.semester, s.room_number
        FROM gate_pass gp
        JOIN students s ON gp.student_id = s.student_id
        WHERE gp.gatepass_id=?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    die("Gatepass not found.");
}

if ($role === 'student' && $userId > 0 && (int) $data['student_id'] !== $userId) {
    die("You are not allowed to view this gatepass.");
}

if ($role === 'hod' && $userDepartment !== '' && strcasecmp(trim((string) $data['department']), trim((string) $userDepartment)) !== 0) {
    die("You are not allowed to view this gatepass.");
}

// HOD can view all statuses for their department (records don't disappear).
// Approve/Reject actions are conditionally rendered below based on status.

$status = trim((string) ($data['status'] ?? ''));
$statusLower = strtolower($status);
$hodApproved = (int) ($data['hod_approved'] ?? 0) === 1;
$wardenApproved = (int) ($data['warden_approved'] ?? 0) === 1;
$isPendingHod = $statusLower === 'pending hod';
$isPendingWarden = $statusLower === 'pending warden';
$isApproved = $statusLower === 'approved';
$displayStatus = $status;
$statusBadgeClass = 'status-pending';
if ($isApproved) {
    $statusBadgeClass = 'status-approved';
} elseif ($statusLower === 'rejected') {
    $statusBadgeClass = 'status-rejected';
}

$isStudent = $role === 'student';
$isHod = $role === 'hod';
$isWarden = $role === 'warden';
$canStudentDownload = $isStudent && $isApproved && $wardenApproved;
$showHodSignature = $hodApproved;
$showWardenSignature = $wardenApproved;
$hodSignaturePath = $showHodSignature ? getFacultySignaturePath($conn, 'hod', (string) ($data['department'] ?? '')) : '';
$wardenSignaturePath = $showWardenSignature ? getFacultySignaturePath($conn, 'warden') : '';

$showHodActions = $isHod && $isPendingHod;
$showWardenActions = $isWarden && $isPendingWarden;

if ($isStudent) {
    $backUrl = 'student_home.php';
} elseif ($isHod) {
    $backUrl = 'hod_home.php';
} else {
    $backUrl = 'warden_home.php';
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Gate Pass</title>

<style>
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: #f4f8fb;
    margin: 0;
}

.pass-box {
    width: 850px;
    margin: 40px auto;
    background: #ffffff;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.header {
    text-align: center;
    border-bottom: 2px solid #057a8d;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.header h2 {
    margin: 0;
    color: #057a8d;
    letter-spacing: 2px;
}

.status-badge {
    display: inline-block;
    margin-top: 12px;
    padding: 6px 14px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 600;
}

.status-pending {
    background: #fff4cc;
    color: #7a5b00;
}

.status-approved {
    background: #d9f5df;
    color: #156a2d;
}

.status-rejected {
    background: #f9d7da;
    color: #8a1f2d;
}

.row {
    display: flex;
    justify-content: space-between;
    margin: 10px 0;
    gap: 10px;
}

.field {
    flex: 1;
}

.label {
    font-size: 13px;
    color: #555;
}

.value {
    border-bottom: 1px solid #333;
    padding: 4px 2px;
    font-weight: 500;
}

.full {
    margin: 12px 0;
}

.declaration {
    margin-top: 20px;
    font-size: 14px;
    line-height: 1.6;
}

/* SIGNATURE ALIGN FIX */
.signature {
    margin-top: 60px;
    display: flex;
    justify-content: space-between;
    text-align: center;
    align-items: flex-end;
}

.signature div {
    width: 30%;
}

.signature img {
    height: 50px;
    display: block;
    margin: auto;
}

/* BUTTON */
.no-print {
    text-align: center;
    margin-top: 25px;
}

button {
    background: #057a8d;
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 8px;
    cursor: pointer;
}

button:hover {
    background: #046574;
}

.action-buttons {
    display: flex;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
}

.action-buttons a,
.action-buttons button {
    text-decoration: none;
}

.approve-btn {
    background: #1f8b4c;
}

.approve-btn:hover {
    background: #166739;
}

.reject-btn {
    background: #c0392b;
}

.reject-btn:hover {
    background: #962d21;
}

.delete-btn {
    background: #c0392b;
}

.delete-btn:hover {
    background: #962d21;
}

button:disabled {
    background: #aab7bf;
    cursor: not-allowed;
}

.action-note {
    margin-top: 12px;
    color: #8a1f2d;
    font-size: 14px;
}

.top-actions {
    display: flex;
    justify-content: flex-start;
    margin-bottom: 10px;
}

.back-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: #ffffff;
    border: 1px solid #d8e4e8;
    color: #0f5f6b;
    font-size: 26px;
    font-weight: 700;
    box-shadow: 0 8px 20px rgba(5, 122, 141, 0.10);
}

.back-link:hover {
    color: #057a8d;
    background: #eef8fb;
}

/* PRINT MODE */
@media print {
    body {
        background: white;
    }

    .pass-box {
        box-shadow: none;
        border: 2px solid black;
    }

    .no-print {
        display: none;
    }
}
</style>

</head>
<body>

<div class="pass-box">

<div class="top-actions no-print">
    <a class="back-link" href="<?php echo htmlspecialchars($backUrl); ?>">
        &#8592;
    </a>
</div>

<div class="header">
    <h2>GATE PASS</h2>
    <div class="status-badge <?php echo $statusBadgeClass; ?>">
        Status: <?php echo htmlspecialchars($displayStatus); ?>
    </div>
</div>

<div class="row">
    <div class="field">
        <div class="label">Gatepass ID</div>
        <div class="value">GP#<?php echo htmlspecialchars((string) $data['gatepass_id']); ?></div>
    </div>
</div>

<div class="row">
    <div class="field">
        <div class="label">Name</div>
        <div class="value"><?php echo $data['name']; ?></div>
    </div>

    <div class="field">
        <div class="label">Date</div>
        <div class="value"><?php echo date("d-m-Y"); ?></div>
    </div>
</div>

<div class="row">
    <div class="field">
        <div class="label">Department</div>
        <div class="value"><?php echo $data['department']; ?></div>
    </div>

    <div class="field">
        <div class="label">Semester</div>
        <div class="value"><?php echo $data['semester']; ?></div>
    </div>

    <div class="field">
        <div class="label">Room No</div>
        <div class="value"><?php echo $data['room_number']; ?></div>
    </div>
</div>

<div class="row">
    <div class="field">
        <div class="label">Student Contact</div>
        <div class="value"><?php echo $data['mobile']; ?></div>
    </div>
</div>

<div class="row">
    <div class="field">
        <div class="label">Parent Contact 1</div>
        <div class="value"><?php echo $data['parent_mobile1']; ?></div>
    </div>

    <div class="field">
        <div class="label">Parent Contact 2</div>
        <div class="value"><?php echo $data['parent_mobile2']; ?></div>
    </div>
</div>

<div class="full">
    <div class="label">Reason</div>
    <div class="value"><?php echo $data['reason']; ?></div>
</div>

<div class="full">
    <div class="label">Place of Visit</div>
    <div class="value"><?php echo $data['location']; ?></div>
</div>

<div class="row">
    <div class="field">
        <div class="label">Leaving</div>
        <div class="value">
            <?php echo htmlspecialchars($data['date_going'] ?? ''); ?>
            <?php if (!empty($data['time_going'])): ?>
                - <?php echo htmlspecialchars($data['time_going']); ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="field">
        <div class="label">Return</div>
        <div class="value">
            <?php echo htmlspecialchars($data['date_return'] ?? ''); ?>
            <?php if (!empty($data['time_return'])): ?>
                - <?php echo htmlspecialchars($data['time_return']); ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="declaration">
    I will leave the premises on 
    <b><?php echo $data['date_going']; ?></b> 
    by <b><?php echo date("h:i A", strtotime($data['time_going'])); ?></b> 
    and will return on 
    <b><?php echo $data['date_return']; ?></b> 
    by <b><?php echo date("h:i A", strtotime($data['time_return'])); ?></b>. 
    I have locked the door and will take care of my belongings.
</div>

<div class="signature">

<div>
    <?php if ($showHodSignature && $hodSignaturePath !== '') { ?>
        <img src="<?php echo htmlspecialchars($hodSignaturePath); ?>">
    <?php } ?>
    <br>HOD Signature
</div>

<div>
    <?php if ($showWardenSignature && $wardenSignaturePath !== '') { ?>
        <img src="<?php echo htmlspecialchars($wardenSignaturePath); ?>">
    <?php } ?>
    <br>Warden Signature
</div>

<div>
    <div style="height:50px;"></div>
    <!-- <hr style="width:70%; margin:auto;"> -->
    Student Signature
</div>

</div>

<div class="no-print">
    <?php if ($isStudent): ?>
        <div class="action-buttons">
            <button onclick="window.print()" <?php echo $canStudentDownload ? '' : 'disabled'; ?>>
                Download / Print
            </button>
            <?php if (in_array($statusLower, ['approved', 'rejected'], true)): ?>
            <form method="POST" action="delete_gatepass.php" onsubmit="return confirm('Delete this gatepass request?');" style="margin: 0;">
                <input type="hidden" name="gatepass_id" value="<?php echo (int) $id; ?>">
                <button type="submit" class="delete-btn">Delete Request</button>
            </form>
            <?php endif; ?>
        </div>
        <?php if (!$canStudentDownload): ?>
            <div class="action-note">Download is enabled only after the gatepass is approved.</div>
        <?php endif; ?>
    <?php elseif ($showHodActions): ?>
        <div class="action-buttons">
            <a href="hod_approve.php?id=<?php echo $id; ?>">
                <button type="button" class="approve-btn">Approve</button>
            </a>
            <a href="hod_reject.php?id=<?php echo $id; ?>">
                <button type="button" class="reject-btn">Reject</button>
            </a>
        </div>
    <?php elseif ($showWardenActions): ?>
        <div class="action-buttons">
            <a href="warden_approve.php?id=<?php echo $id; ?>">
                <button type="button" class="approve-btn">Approve</button>
            </a>
            <a href="warden_reject.php?id=<?php echo $id; ?>">
                <button type="button" class="reject-btn">Reject</button>
            </a>
        </div>
    <?php endif; ?>
</div>

</div>

<?php if ($flashMessage !== ''): ?>
<script>
    alert(<?php echo json_encode($flashMessage); ?>);
</script>
<?php endif; ?>

</body>
</html>
