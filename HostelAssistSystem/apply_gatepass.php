<?php
session_start();
include("config.php");
include("gatepass_workflow.php");
include("notification_helpers.php");

// ===== HOLIDAY FUNCTION =====
function isHoliday($date) {

    $year = date("Y");

    // Tallyfy API
    $url = "https://tallyfy.com/national-holidays/api/IN/$year.json";

    $response = @file_get_contents($url);

    // ✅ Check Sunday
    if (date("l", strtotime($date)) == "Sunday") {
        return true;
    }

    if ($response === FALSE) {
        return false; // fallback
    }

    $data = json_decode($response, true);

    foreach ($data['holidays'] as $holiday) {
        if ($holiday['date'] == $date) {
            return true;
        }
    }

    return false;
}


// ===== SESSION CHECK =====
$student_id = isset($_SESSION['student_id']) ? $_SESSION['student_id'] : (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null);

if (!$student_id) {
    echo "<script>alert('Session expired. Please login again.'); window.location='login.html';</script>";
    exit;
}


// ===== APPLY LOGIC =====
if(isset($_POST['apply'])) {

    $location = $_POST['location'];
    $reason = $_POST['reason'];
    $date_going = $_POST['date_going'];
    $time_going = $_POST['time_going'];
    $date_return = $_POST['date_return'];
    $time_return = $_POST['time_return'];
    $issueDate = date('Y-m-d');
    $issueTime = date('H:i:s');

    // 🔥 CHECK WORKING HOURS - determine initial status
    $needsHodApproval = gatepassNeedsHodApprovalAtIssueTime($issueDate, $issueTime);
    $status = $needsHodApproval ? "Pending HOD" : "Pending Warden";

    $sql = "INSERT INTO gate_pass 
            (student_id, location, reason, date_going, time_going, date_return, time_return, issue_date, issue_time, status, hod_approved, warden_approved) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssssssss", $student_id, $location, $reason, $date_going, $time_going, $date_return, $time_return, $issueDate, $issueTime, $status);

    if($stmt->execute()){
        $gatepassId = (int) $stmt->insert_id;

        $studentDetailsStmt = $conn->prepare("SELECT name, department FROM students WHERE student_id = ?");
        if ($studentDetailsStmt) {
            $studentDetailsStmt->bind_param("i", $student_id);
            $studentDetailsStmt->execute();
            $studentDetails = $studentDetailsStmt->get_result()->fetch_assoc();
            $studentDetailsStmt->close();
        } else {
            $studentDetails = null;
        }

        $studentName = $studentDetails && !empty($studentDetails['name']) ? $studentDetails['name'] : 'Student';
        $studentDepartment = $studentDetails && !empty($studentDetails['department']) ? $studentDetails['department'] : '';
        $notificationUrl = buildNotificationTargetUrl('gatepass', $gatepassId);

        if ($needsHodApproval) {
            notifyFacultyByRole(
                $conn,
                'hod',
                'New gatepass request',
                $studentName . ' submitted gatepass GP#' . $gatepassId . ' for HOD approval.',
                'gatepass',
                $gatepassId,
                $notificationUrl,
                $studentDepartment,
                'student',
                $student_id
            );

            notifyFacultyByRole(
                $conn,
                'warden',
                'New gatepass request',
                $studentName . ' submitted gatepass GP#' . $gatepassId . '. It is currently waiting for HOD approval.',
                'gatepass',
                $gatepassId,
                $notificationUrl,
                null,
                'student',
                $student_id
            );
        } else {
            notifyFacultyByRole(
                $conn,
                'warden',
                'New gatepass request',
                $studentName . ' submitted gatepass GP#' . $gatepassId . ' for warden approval.',
                'gatepass',
                $gatepassId,
                $notificationUrl,
                null,
                'student',
                $student_id
            );
        }

        echo "<script>alert('Gate Pass Applied Successfully'); window.location='student_home.php';</script>";
        exit;
    } else {
        echo "<script>alert('Error applying gate pass');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Apply Gate Pass</title>

<style>
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: linear-gradient(135deg, #e6f4f7, #d4eef3);
    color: #0f2027;
}

.container {
    width: 460px;
    max-width: 92%;
    margin: 50px auto;
    background: #ffffff;
    padding: 28px;
    border-radius: 14px;
    border: none;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

/* TITLE */
h2 {
    text-align: center;
    margin-bottom: 22px;
    color: #057a8d; /* teal */
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

/* LABEL */
label {
    display: block;
    margin: 10px 0 4px;
    color: #057a8d;
    font-weight: 600;
}

/* INPUT FIELDS */
input, textarea {
    width: 100%;
    padding: 12px 14px;
    margin-bottom: 12px;
    border: 1px solid #cde7ee;
    border-radius: 10px;
    background: #f8fcff;
    color: #333;
    box-sizing: border-box;
    transition: 0.2s;
}

input:focus, textarea:focus {
    outline: none;
    border-color: #057a8d;
    box-shadow: 0 0 0 3px rgba(5, 122, 141, 0.2);
}

/* BUTTON */
button {
    width: 100%;
    padding: 13px;
    margin-top: 8px;
    background: #057a8d;
    color: #ffffff;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s;
}

button:hover {
    background: #046574;
    box-shadow: 0 6px 18px rgba(5, 122, 141, 0.3);
}
button:active {
    transform: translateY(1px);
    box-shadow: 0 4px 10px rgba(5, 122, 141, 0.35);
}

button:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 22px rgba(0,0,0,0.28);
}

button:active {
    transform: translateY(0);
}

</style>

</head>
<body>

<div class="container">

<h2>Apply Gate Pass</h2>

<form method="POST">

<label>Location</label>
<input type="text" name="location" required>

<label>Reason</label>
<textarea name="reason" required></textarea>

<label>Leaving Date</label>
<input type="date" id="date_going" name="date_going" required>

<label>Return Date</label>
<input type="date" id="date_return" name="date_return" required>

<label>Leaving Time</label>
<input type="time" name="time_going" required>

<label>Return Time</label>
<input type="time" name="time_return" required>

<button type="submit" name="apply">Apply</button>

</form>

</div>

<script>
const dateGoing = document.getElementById('date_going');
const dateReturn = document.getElementById('date_return');

// Minimum is today for going date
const today = new Date().toISOString().split('T')[0];
dateGoing.min = today;

dateGoing.addEventListener('change', function () {
    if (!this.value) { return; }

    dateReturn.min = this.value;

    if (dateReturn.value && dateReturn.value < this.value) {
        dateReturn.value = this.value;
    }
});

// Make sure return date never before going date
dateReturn.addEventListener('focus', function () {
    if (dateGoing.value) {
        this.min = dateGoing.value;
    } else {
        this.min = today;
    }
});
</script>

</body>
</html>
