<?php
header('Content-Type: application/json');
require("connection.php");

function getStudentDetails($nfc_uid) {
    global $con;
    
    $sql = "SELECT * FROM class_list WHERE nfc_uid = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("s", $nfc_uid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

function determineStatus($time) {
    $cutoff = strtotime('08:00:00');
    $current = strtotime($time);
    return ($current > $cutoff) ? 'LATE' : 'ON TIME';
}

$json_str = file_get_contents('php://input');

if (!empty($json_str)) {
    $nfc_data = json_decode($json_str, true);
    
    if (json_last_error() === JSON_ERROR_NONE && isset($nfc_data['uid'])) {
        $student = getStudentDetails($nfc_data['uid']);
        
        if ($student) {
            $current_time = date('H:i:s');
            $status = determineStatus($current_time);
            
            // Get guardian details from student record
            $guardian_phone = $student['guardian_num'];
            $guardian_name = $student['guardian_name'];
            $student_name = $student['surname'] . ', ' . $student['first_name'];
            
            // Build message with explicit line breaks and guardian's name
            $message = "Dear {$guardian_name}," . PHP_EOL . PHP_EOL;
            $message .= "This is to inform you that {$student_name} has successfully checked in at school today." . PHP_EOL;
            $message .= "Date: " . date('F j, Y') . PHP_EOL;
            $message .= "Time: {$current_time}" . PHP_EOL;
            $message .= "Status: {$status}" . PHP_EOL . PHP_EOL;
            $message .= "- School Administration";

            // Properly escape the message for shell execution
            $escaped_message = escapeshellarg($message);
            $escaped_phone = escapeshellarg($guardian_phone);
            
            // Execute Python script with properly escaped arguments
            $command = "python ./py/smsnotif.py {$escaped_phone} {$escaped_message}";
            $output = shell_exec($command);

            // Log the output for debugging
            error_log("SMS Command: " . $command);
            error_log("SMS Script Output: " . $output);

            echo json_encode([
                'success' => true,
                'student' => [
                    'name' => $student_name,
                    'time' => $current_time,
                    'status' => $status,
                    'notification_sent' => true
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'error' => 'Student not found'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false, 
            'error' => 'Invalid JSON data'
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'error' => 'No data received'
    ]);
}
?>