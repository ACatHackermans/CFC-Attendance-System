<?php

$dbhost = "localhost";
$dbuser = "root";
$dbpass = "";
$dbname = "cfc_attendance_system_db";

try {
    $con = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
}
catch(mysqli_sql_exception) {
    die("Failed to connect".mysqli_connect_error());
}