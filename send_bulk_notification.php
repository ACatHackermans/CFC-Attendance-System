<?php
header('Content-Type: application/json');
require("connection.php");

function sendBulkNotification($student_data) {
    try {
        // Format the message for this student
        $message = "Dear {$student_data['guardian_name']}," . PHP_EOL . PHP_EOL;
        $message .= "Good Day! This is to inform you that {$student_data['student_name']} has successfully checked in at school today." . PHP_EOL;
        $message .= "Date: " . date('F j, Y') . PHP_EOL;
        $message .= "Time: {$student_data['time_in']}" . PHP_EOL;
        $message .= "Status: {$student_data['status']}" . PHP_EOL . PHP_EOL;
        $message .= "- CFC School Administration";

        // Properly escape the message and phone number for shell execution
        $escaped_message = escapeshellarg($message);
        $formatted_phone = "+63" . $student_data['guardian_num'];
        $escaped_phone = escapeshellarg($formatted_phone);
        
        // Execute Python script with properly escaped arguments
        $command = "python ./py/smsnotif.py {$escaped_phone} {$escaped_message}";
        $output = shell_exec($command);

        // Log the output for debugging
        error_log("SMS Command for {$student_data['student_name']}: " . $command);
        error_log("SMS Script Output: " . $output);

        return true;
    } catch (Exception $e) {
        error_log("Error sending notification: " . $e->getMessage());
        return false;
    }
}

// If this file is called directly with student data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json_str = file_get_contents('php://input');
    $data = json_decode($json_str, true);
    
    if (isset($data['student_data'])) {
        $result = sendBulkNotification($data['student_data']);
        echo json_encode(['success' => $result]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No student data provided']);
    }
}
?>