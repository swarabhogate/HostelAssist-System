<?php
include("config.php");
include_once("system_helpers.php");

$columnCheck = mysqli_query($conn, "SHOW COLUMNS FROM students LIKE 'year'");
if ($columnCheck && mysqli_num_rows($columnCheck) === 0) {
    mysqli_query($conn, "ALTER TABLE students ADD COLUMN `year` VARCHAR(50) NULL AFTER department");
}

// Get form data
$name = $_POST['first_name'] . " " . $_POST['middle_name'] . " " . $_POST['last_name'];
$email = normalizeEmailAddress($_POST['email'] ?? '');
$password = $_POST['password'];
$mobile = $_POST['mobile'];
$parent_mobile1 = $_POST['parent_mobile1'];
$parent_mobile2 = $_POST['parent_mobile2'];
$department = $_POST['department'];
$year = $_POST['year'];
$semester = $_POST['semester'];
$room_number = $_POST['room_number'];

if (
    $mobile !== '' &&
    (
        $mobile === $parent_mobile1 ||
        ($parent_mobile2 !== '' && $mobile === $parent_mobile2) ||
        ($parent_mobile1 !== '' && $parent_mobile2 !== '' && $parent_mobile1 === $parent_mobile2)
    )
) {
    echo "<script>alert('Student mobile number, Parent Mobile No 1, and Parent Mobile No 2 must all be different.'); window.location='index.html';</script>";
    exit;
}

if (!isValidSemesterForYear($year, $semester)) {
    echo "<script>alert('Selected semester does not match the selected year.'); window.location='index.html';</script>";
    exit;
}

// Insert data (plain password)
$sql = "INSERT INTO students 
(name, email, mobile, parent_mobile1, parent_mobile2, department, `year`, semester, room_number, password) 
VALUES 
('$name', '$email', '$mobile', '$parent_mobile1', '$parent_mobile2', '$department', '$year', '$semester', '$room_number', '$password')";

if (mysqli_query($conn, $sql)) {
    echo "<script>alert('Registration Successful'); window.location='login.html';</script>";
} else {
    echo "Error: " . mysqli_error($conn);
}

mysqli_close($conn);
?>
