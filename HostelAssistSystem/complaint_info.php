<?php
session_start();
include("config.php");
include("notification_helpers.php");

$userRole = isset($_SESSION['role']) ? strtolower(trim((string) $_SESSION['role'])) : '';
$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
$complaintId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isWarden = $userRole === 'warden';
$isStudent = $userRole === 'student';
$flashMessage = '';
$errorMessage = '';

function getComplaintDashboardUrl($role)
{
    if ($role === 'student') {
        return 'Student_home.php';
    }

    if ($role === 'hod') {
        return 'hod_home.php';
    }

    return 'warden_home.php';
}

function getComplaintStatusClass($status)
{
    $statusClass = strtolower(trim((string) $status));

    if ($statusClass === 'pending') {
        return 'pending';
    }

    if ($statusClass === 'assigned' || $statusClass === 'in progress') {
        return 'assigned';
    }

    if (in_array($statusClass, ['resolved'], true)) {
        return 'resolved';
    }

    return 'default';
}

if ($complaintId <= 0) {
    $errorMessage = 'Invalid complaint ID.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $isWarden) {
    $notificationUrl = buildNotificationTargetUrl('complaint', $complaintId);

    $complaintStmt = $conn->prepare("SELECT complaint_id, student_id, title, status, remark FROM complaints WHERE complaint_id = ?");
    if ($complaintStmt) {
        $complaintStmt->bind_param("i", $complaintId);
        $complaintStmt->execute();
        $complaintResult = $complaintStmt->get_result();
        $complaint = $complaintResult ? $complaintResult->fetch_assoc() : null;
        $complaintStmt->close();
    } else {
        $complaint = null;
    }

    if (!$complaint) {
        $errorMessage = 'Complaint not found.';
    } elseif (in_array(strtolower(trim((string) $complaint['status'])), ['resolved'], true)) {
        $errorMessage = 'This complaint is already resolved and cannot be modified.';
    } elseif (isset($_POST['update_status'])) {
        $newStatus = trim((string) ($_POST['status'] ?? ''));
        $allowedStatuses = ['Pending', 'Assigned', 'In Progress', 'Resolved'];

        if (!in_array($newStatus, $allowedStatuses, true)) {
            $errorMessage = 'Please select a valid status.';
        } else {
            $statusIsComplete = in_array(strtolower($newStatus), ['resolved'], true);
            if ($statusIsComplete) {
                $updateStmt = $conn->prepare("UPDATE complaints SET status = ?, completion_date = CURDATE() WHERE complaint_id = ?");
            } else {
                $updateStmt = $conn->prepare("UPDATE complaints SET status = ?, completion_date = NULL WHERE complaint_id = ?");
            }

            if ($updateStmt) {
                $updateStmt->bind_param("si", $newStatus, $complaintId);
                if ($updateStmt->execute()) {
                    $flashMessage = 'Complaint status updated successfully.';

                    createNotification(
                        $conn,
                        'student',
                        (int) $complaint['student_id'],
                        'Complaint status updated',
                        'Complaint #' . $complaintId . ' status changed to ' . $newStatus . '.',
                        'complaint',
                        $complaintId,
                        $notificationUrl,
                        'warden',
                        $userId
                    );

                } else {
                    $errorMessage = 'Unable to update complaint status.';
                }
                $updateStmt->close();
            } else {
                $errorMessage = 'Unable to prepare complaint status update.';
            }
        }
    } elseif (isset($_POST['save_remark'])) {
        $remark = trim((string) ($_POST['remark'] ?? ''));

        if ($remark === '') {
            $errorMessage = 'Remark cannot be empty.';
        } else {
            $remarkStmt = $conn->prepare("UPDATE complaints SET remark = ? WHERE complaint_id = ?");
            if ($remarkStmt) {
                $remarkStmt->bind_param("si", $remark, $complaintId);
                if ($remarkStmt->execute()) {
                    $flashMessage = 'Remark sent to student successfully.';

                    createNotification(
                        $conn,
                        'student',
                        (int) $complaint['student_id'],
                        'New remark on complaint',
                        'Warden added a remark on complaint #' . $complaintId . ': ' . $remark,
                        'complaint',
                        $complaintId,
                        $notificationUrl,
                        'warden',
                        $userId
                    );

                } else {
                    $errorMessage = 'Unable to save remark.';
                }
                $remarkStmt->close();
            } else {
                $errorMessage = 'Unable to prepare remark update.';
            }
        }
    }
}

$complaintData = null;
$studentName = '';
$studentMobile = '';

if ($complaintId > 0) {
    $sql = "SELECT complaint_id, student_id, title, description, status, remark, photo, completion_date, submission_date, show_name
            FROM complaints
            WHERE complaint_id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $complaintId);
        $stmt->execute();
        $result = $stmt->get_result();
        $complaintData = $result ? $result->fetch_assoc() : null;
        $stmt->close();
    }

    if ($complaintData && $complaintData['show_name'] === 'yes') {
        $studentStmt = $conn->prepare("SELECT name, mobile FROM students WHERE student_id = ?");
        if ($studentStmt) {
            $studentStmt->bind_param("i", $complaintData['student_id']);
            $studentStmt->execute();
            $studentResult = $studentStmt->get_result();
            $student = $studentResult ? $studentResult->fetch_assoc() : null;
            if ($student) {
                $studentName = (string) $student['name'];
                $studentMobile = (string) $student['mobile'];
            }
            $studentStmt->close();
        }
    }

    if (!$complaintData && $errorMessage === '') {
        $errorMessage = 'Complaint not found.';
    }
}

$isCompleted = $complaintData && in_array(strtolower(trim((string) $complaintData['status'])), ['resolved'], true);
$dashboardUrl = getComplaintDashboardUrl($userRole);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HostelAssist - Complaint Info</title>
<style>
    body {
        margin: 0;
        font-family: 'Segoe UI', sans-serif;
        background: linear-gradient(135deg, #177e89, #0f5f6b);
        min-height: 100vh;
        padding: 24px;
        box-sizing: border-box;
    }

    .container {
        position: relative;
        background: #f0fbfc;
        margin: 0 auto;
        width: 100%;
        max-width: 980px;
        border-radius: 20px;
        box-shadow: 0 15px 40px rgba(0,0,0,0.25);
        padding: 34px 34px 28px;
    }

    .back-link {
        position: absolute;
        top: 18px;
        left: 18px;
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-size: 24px;
        background: #177e89;
        color: #fff;
        box-shadow: 0 8px 18px rgba(23, 126, 137, 0.28);
    }

    h2 {
        margin: 0 0 24px;
        text-align: center;
        color: #177e89;
    }

    .alert,
    .error {
        padding: 14px 16px;
        border-radius: 10px;
        margin-bottom: 18px;
        font-size: 14px;
    }

    .alert {
        background: #d8f3dc;
        color: #1b5e20;
    }

    .error {
        background: #f8d7da;
        color: #721c24;
    }

    .info-box {
        background: #fff;
        padding: 24px;
        border-radius: 18px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    }

    table {
        width: 100%;
        border-collapse: collapse;
        overflow: hidden;
        border-radius: 14px;
    }

    th,
    td {
        padding: 14px 16px;
        border-bottom: 1px solid #e3eef1;
        text-align: left;
        vertical-align: top;
        word-break: break-word;
    }

    th {
        width: 220px;
        background: #f4fbfc;
        color: #177e89;
        font-weight: 700;
    }

    tr:last-child th,
    tr:last-child td {
        border-bottom: none;
    }

    .status {
        display: inline-block;
        padding: 7px 14px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 700;
        background: #eaeaea;
        color: #333;
    }

    .status.pending {
        background: #fff3cd;
        color: #856404;
    }

    .status.assigned {
        background: #c7f0f5;
        color: #177e89;
    }

    .status.resolved {
        background: #c8f5d3;
        color: #1b8f3a;
    }

    .photo-thumb {
        max-width: 220px;
        width: 100%;
        border-radius: 12px;
        border: 1px solid #cfdfe3;
        cursor: zoom-in;
    }

    .actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 18px;
        margin-top: 24px;
    }

    .action-card {
        background: #f8fcfd;
        border: 1px solid #d8eaee;
        border-radius: 16px;
        padding: 18px;
    }

    .action-card h3 {
        margin: 0 0 14px;
        color: #177e89;
        font-size: 18px;
    }

    .field-label {
        display: block;
        margin-bottom: 8px;
        font-size: 14px;
        font-weight: 600;
        color: #24545b;
    }

    select,
    textarea {
        width: 100%;
        padding: 12px 14px;
        border: 1px solid #bfd6db;
        border-radius: 10px;
        font-size: 14px;
        box-sizing: border-box;
        outline: none;
    }

    textarea {
        min-height: 130px;
        resize: vertical;
    }

    .action-btn {
        margin-top: 14px;
        border: none;
        border-radius: 10px;
        background: linear-gradient(135deg, #177e89, #0f5f6b);
        color: #fff;
        padding: 11px 18px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
    }

    .danger-card {
        margin-top: 24px;
        background: #fff5f5;
        border: 1px solid #f0c8c8;
        border-radius: 16px;
        padding: 18px;
    }

    .danger-card h3 {
        margin: 0 0 10px;
        color: #b42318;
        font-size: 18px;
    }

    .danger-card p {
        margin: 0 0 14px;
        color: #7a271a;
        font-size: 14px;
    }

    .delete-btn {
        border: none;
        border-radius: 10px;
        background: #c0392b;
        color: #fff;
        padding: 11px 18px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
    }

    .image-modal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.82);
        justify-content: center;
        align-items: center;
        z-index: 9999;
        padding: 24px;
    }

    .image-modal.active {
        display: flex;
    }

    .image-modal-content {
        position: relative;
        max-width: 92vw;
        max-height: 92vh;
    }

    .image-modal-content img {
        max-width: 100%;
        max-height: 92vh;
        border-radius: 14px;
    }

    .modal-close-btn {
        position: absolute;
        top: -14px;
        right: -14px;
        width: 34px;
        height: 34px;
        border-radius: 50%;
        border: none;
        background: #fff;
        font-size: 18px;
        cursor: pointer;
    }

    @media (max-width: 768px) {
        body {
            padding: 14px;
        }

        .container {
            padding: 54px 16px 18px;
        }

        th,
        td {
            display: block;
            width: auto;
        }

        th {
            border-bottom: none;
            padding-bottom: 6px;
        }

        td {
            padding-top: 0;
        }

        tr {
            display: block;
            border-bottom: 1px solid #e3eef1;
            padding: 10px 0;
        }

        tr:last-child {
            border-bottom: none;
        }
    }
</style>
</head>
<body>
<div class="container">
    <a class="back-link" href="<?php echo htmlspecialchars($dashboardUrl); ?>" aria-label="Back to dashboard">&#8592;</a>
    <h2>Complaint Info</h2>

    <?php if ($flashMessage !== '') { ?>
        <div class="alert"><?php echo htmlspecialchars($flashMessage); ?></div>
    <?php } ?>

    <?php if ($errorMessage !== '') { ?>
        <div class="error"><?php echo htmlspecialchars($errorMessage); ?></div>
    <?php } ?>

    <?php if ($complaintData) { ?>
        <div class="info-box">
            <table>
                <tr>
                    <th>Complaint ID</th>
                    <td>#<?php echo htmlspecialchars((string) $complaintData['complaint_id']); ?></td>
                </tr>
                <tr>
                    <th>Student Name</th>
                    <td><?php echo $complaintData['show_name'] === 'yes' && $studentName !== '' ? htmlspecialchars($studentName) : 'Anonymous'; ?></td>
                </tr>
                <tr>
                    <th>Student Mobile</th>
                    <td><?php echo $complaintData['show_name'] === 'yes' && $studentMobile !== '' ? htmlspecialchars($studentMobile) : 'Anonymous'; ?></td>
                </tr>
                <tr>
                    <th>Title</th>
                    <td><?php echo htmlspecialchars((string) $complaintData['title']); ?></td>
                </tr>
                <tr>
                    <th>Description</th>
                    <td><?php echo nl2br(htmlspecialchars((string) $complaintData['description'])); ?></td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td><span class="status <?php echo htmlspecialchars(getComplaintStatusClass($complaintData['status'])); ?>"><?php echo htmlspecialchars((string) $complaintData['status']); ?></span></td>
                </tr>
                <tr>
                    <th>Remark</th>
                    <td><?php echo trim((string) $complaintData['remark']) !== '' ? nl2br(htmlspecialchars((string) $complaintData['remark'])) : '-'; ?></td>
                </tr>
                <tr>
                    <th>Photo</th>
                    <td>
                        <?php if (!empty($complaintData['photo'])) { ?>
                            <img id="complaintImage" class="photo-thumb" src="uploads/<?php echo htmlspecialchars((string) $complaintData['photo']); ?>" alt="Complaint photo">
                        <?php } else { ?>
                            -
                        <?php } ?>
                    </td>
                </tr>
            </table>

            <?php if ($isWarden && !$isCompleted) { ?>
                <div class="actions">
                    <div class="action-card">
                        <h3>Update Status</h3>
                        <form method="POST">
                            <label class="field-label" for="status">Select complaint status</label>
                            <select id="status" name="status" required>
                                <?php
                                $statusOptions = ['Pending', 'Assigned', 'In Progress', 'Resolved'];
                                $currentStatus = trim((string) $complaintData['status']);
                                foreach ($statusOptions as $option) {
                                    $selected = strcasecmp($currentStatus, $option) === 0 ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($option) . '" ' . $selected . '>' . htmlspecialchars($option) . '</option>';
                                }
                                ?>
                            </select>
                            <button type="submit" name="update_status" class="action-btn">Update Status</button>
                        </form>
                    </div>

                    <div class="action-card">
                        <h3>Add Remark</h3>
                        <form method="POST">
                            <label class="field-label" for="remark">Send a message to the student</label>
                            <textarea id="remark" name="remark" placeholder="Write remark for student..." required><?php echo htmlspecialchars((string) $complaintData['remark']); ?></textarea>
                            <button type="submit" name="save_remark" class="action-btn">Save Remark</button>
                        </form>
                    </div>
                </div>
            <?php } elseif ($isWarden && $isCompleted) { ?>
                <div class="actions" style="margin-top:24px;">
                    <div class="action-card" style="border-color:#a8d5b5;background:linear-gradient(135deg,#f0fff4,#e6f9ec);">
                        <h3 style="color:#1b8f3a;">&#9989; Complaint Resolved</h3>
                        <p style="color:#2d6a4f;margin:0;font-size:14px;">This complaint has been marked as <strong>Resolved</strong>. </p>
                    </div>
                </div>
            <?php } ?>

            <?php if ($isStudent && (int) $complaintData['student_id'] === $userId && $isCompleted) { ?>
                <form method="POST" action="delete_complaint.php" onsubmit="return confirm('Delete this complaint?');" style="margin-top: 24px;">
                    <input type="hidden" name="complaint_id" value="<?php echo (int) $complaintData['complaint_id']; ?>">
                    <button type="submit" class="delete-btn">Delete Complaint</button>
                </form>
            <?php } ?>
        </div>
    <?php } ?>
</div>

<div id="imageModal" class="image-modal" aria-hidden="true">
    <div class="image-modal-content">
        <button type="button" class="modal-close-btn" aria-label="Close image">&times;</button>
        <img id="modalImage" src="" alt="Complaint preview">
    </div>
</div>

<script>
const complaintImage = document.getElementById('complaintImage');
const imageModal = document.getElementById('imageModal');
const modalImage = document.getElementById('modalImage');
const closeModalBtn = document.querySelector('.modal-close-btn');

if (complaintImage && imageModal && modalImage) {
    complaintImage.addEventListener('click', function() {
        modalImage.src = this.src;
        imageModal.classList.add('active');
        imageModal.setAttribute('aria-hidden', 'false');
    });
}

function closeImageModal() {
    if (!imageModal || !modalImage) {
        return;
    }

    imageModal.classList.remove('active');
    imageModal.setAttribute('aria-hidden', 'true');
    modalImage.src = '';
}

if (closeModalBtn) {
    closeModalBtn.addEventListener('click', closeImageModal);
}

if (imageModal) {
    imageModal.addEventListener('click', function(event) {
        if (event.target === imageModal) {
            closeImageModal();
        }
    });
}

window.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && imageModal && imageModal.classList.contains('active')) {
        closeImageModal();
    }
});
</script>
</body>
</html>
