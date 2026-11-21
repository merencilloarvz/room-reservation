<?php
// database settings. these are the xampp defaults (root, no password).
// on free hosting i replace these 4 with the values from the control panel
$host = "localhost";
$user = "root";
$pass = "";
$db   = "room_reservation";

// by default mysqli throws exceptions on errors, i turn that off
// and check the connection error myself below
mysqli_report(MYSQLI_REPORT_OFF);

// connect to mysql. $conn is the connection object that every page uses
$conn = new mysqli($host, $user, $pass, $db);

// if the connection failed there's no point loading the page, so stop here
if ($conn->connect_error) {
    die("Database connection failed: " . htmlspecialchars($conn->connect_error));
}

// utf8mb4 so characters like the peso sign show up correctly
$conn->set_charset("utf8mb4");

// free hosting servers run on UTC, so i force philippine time here.
// it has to be set in php AND in mysql, otherwise "today" gets mixed up
date_default_timezone_set('Asia/Manila');
$conn->query("SET time_zone = '+08:00'");
