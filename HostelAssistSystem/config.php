<?php
date_default_timezone_set('Asia/Kolkata');

// Database connection
$conn = mysqli_connect("localhost", "root", "", "hostel_assist");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_USERNAME', 'swarabhogate001@gmail.com');
define('MAIL_PASSWORD', 'pkhl cgmp hexr nnrz'); // App password
define('MAIL_PORT', 587);
define('MAIL_FROM_ADDRESS', 'swarabhogate001@gmail.com');
define('MAIL_FROM_NAME', 'HostelAssist');
?>