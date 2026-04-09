<?php
session_start();
include("config.php");
include("notification_helpers.php");
include_once("system_helpers.php");

ensureComplaintWorkflowSchema($conn);

// Assume student is logged in
$student_id = isset($_SESSION['student_id']) ? (int) $_SESSION['student_id'] : (isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0);

if ($student_id <= 0) {
    header("Location: login.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $category = $_POST['category'];
    $description = $_POST['description'];

    // Anonymous check
    $show_name = isset($_POST['anonymous']) ? 'no' : 'yes';

    // File upload
    $image_name = "";
    if (!empty($_FILES['image']['name'])) {
        $image_name = time() . "_" . $_FILES['image']['name'];
        $target = "uploads/" . $image_name;
        move_uploaded_file($_FILES['image']['tmp_name'], $target);
    }

    // Insert into database
    $studentDepartment = '';
    $studentStmt = $conn->prepare("SELECT department FROM students WHERE student_id = ?");
    if ($studentStmt) {
        $studentStmt->bind_param("i", $student_id);
        $studentStmt->execute();
        $studentResult = $studentStmt->get_result();
        $studentRow = $studentResult ? $studentResult->fetch_assoc() : null;
        if ($studentRow) {
            $studentDepartment = trim((string) ($studentRow['department'] ?? ''));
        }
        $studentStmt->close();
    }

    $status = 'Pending';

    $sql = "INSERT INTO complaints 
    (student_id, title, description, status, show_name, photo, submission_date)
    VALUES (?, ?, ?, ?, ?, ?, CURDATE())";

    // ✅ FIX: prepare statement
    $stmt = $conn->prepare($sql);

    // Bind parameters
    $stmt->bind_param("isssss", $student_id, $category, $description, $status, $show_name, $image_name);

    if ($stmt->execute()) {

        $complaint_id = $stmt->insert_id;

        $studentName = isset($_SESSION['name']) && trim((string) $_SESSION['name']) !== '' ? trim((string) $_SESSION['name']) : 'Student';
        $notificationUrl = buildNotificationTargetUrl('complaint', $complaint_id);

        notifyFacultyByRole(
            $conn,
            'warden',
            'New complaint submitted',
            $studentName . ' submitted complaint #' . $complaint_id . ' under ' . $category . '.',
            'complaint',
            $complaint_id,
            $notificationUrl,
            null,
            'student',
            $student_id
        );

        header("Location: success.php?id=" . $complaint_id);
        exit();

    } else {
        echo "Error: " . $conn->error;
    }
}
?>
