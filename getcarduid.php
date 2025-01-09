<?php
header('Content-Type: application/json');

// Call the Python script to read the NFC card UID
$script_path = "C:\\xampp\\htdocs\\CFC-Attendance-System-main\\py\\readcarduid.py";
$output = shell_exec("python $script_path 2>&1");

echo $output;
?>