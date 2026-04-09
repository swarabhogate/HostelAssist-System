<?php

define('COLLEGE_HOUR_START', '10:00');
define('COLLEGE_HOUR_END', '17:00');

function isHolidayForGatepass($date) {
    if (date("l", strtotime($date)) === "Sunday") {
        return true;
    }

    $year = date("Y", strtotime($date));
    $url = "https://tallyfy.com/national-holidays/api/IN/$year.json";
    $response = @file_get_contents($url);

    if ($response === false) {
        return false;
    }

    $data = json_decode($response, true);

    if (!isset($data['holidays']) || !is_array($data['holidays'])) {
        return false;
    }

    foreach ($data['holidays'] as $holiday) {
        if (($holiday['date'] ?? '') === $date) {
            return true;
        }
    }

    return false;
}

function isWorkingDayForGatepass($date) {
    return !isHolidayForGatepass($date);
}

function isWithinCollegeHoursForGatepass($time) {
    $timeValue = strtotime($time);
    $startValue = strtotime(COLLEGE_HOUR_START);
    $endValue = strtotime(COLLEGE_HOUR_END);

    if ($timeValue === false || $startValue === false || $endValue === false) {
        return false;
    }

    return $timeValue >= $startValue && $timeValue <= $endValue;
}

function gatepassNeedsHodApprovalAtIssueTime($issuedDate = null, $issuedTime = null) {
    $issuedDate = $issuedDate ?: date('Y-m-d');
    $issuedTime = $issuedTime ?: date('H:i');

    return isWorkingDayForGatepass($issuedDate) && isWithinCollegeHoursForGatepass($issuedTime);
}
?>
