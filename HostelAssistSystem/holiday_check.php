<?php

function isHoliday($date) {

    $year = date("Y");

    // 🔥 Tallyfy API (dynamic year)
    $url = "https://tallyfy.com/national-holidays/api/IN/$year.json";

    $response = file_get_contents($url);

    if ($response === FALSE) {
        return false; // fallback
    }

    $data = json_decode($response, true);

    // ✅ Check Sunday (weekly holiday)
    if (date("l", strtotime($date)) == "Sunday") {
        return true;
    }

    // ✅ Check API holidays
    foreach ($data['holidays'] as $holiday) {
        if ($holiday['date'] == $date) {
            return true;
        }
    }

    return false;
}
?>