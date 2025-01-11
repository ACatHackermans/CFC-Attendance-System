<?php
header('Content-Type: application/json');
require("connection.php");

// Get the last activity time
$last_activity_file = "./res/last_attendance_activity.txt";
$current_time = time();
$inactivity_threshold = 30;
$needs_notification = false;

// Check if notifications have already been sent today
$notifications_sent_file = "./res/notifications_sent_today.txt";
$current_date = date('Y-m-d');

if (file_exists($notifications_sent_file)) {
    $last_notification_date = trim(file_get_contents($notifications_sent_file));
    if ($last_notification_date !== $current_date) {
        $needs_notification = true;
    }
} else {
    $needs_notification = true;
}

if (file_exists($last_activity_file)) {
    $last_activity = (int)file_get_contents($last_activity_file);
    if (($current_time - $last_activity) >= $inactivity_threshold && $needs_notification) {
        try {
            // Get all students who logged in today
            $sql = "SELECT cl.student_num, cl.surname, cl.first_name, cl.guardian_name, 
                          cl.guardian_num, ar.time_in, ar.status_today
                   FROM class_list cl
                   INNER JOIN attendance_report ar ON cl.student_num = ar.student_num
                   WHERE DATE(ar.time_in) = CURRENT_DATE
                   AND ar.status_today IN ('present', 'late')";
            
            $result = $con->query($sql);
            $notification_count = 0;
            $error_count = 0;

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $message = "Good Day " . $row['guardian_name'] . "|" .
                              "This is to inform you that " . $row['surname'] . ", " . $row['first_name'] . " has checked in at school today.|" .
                              "Date: " . date('F j, Y') . "|" .
                              "Time: " . date('h:i A', strtotime($row['time_in'])) . "|" .
                              "Status: " . ucfirst($row['status_today']) . "|" .
                              "- CFC School Administration";

                    // Format phone number
                    $phone_number = $row['guardian_num'];
                    if (substr($phone_number, 0, 1) === '0') {
                        $phone_number = '+63' . substr($phone_number, 1);
                    } elseif (substr($phone_number, 0, 2) !== '+63') {
                        $phone_number = '+63' . $phone_number;
                    }

                    // Execute SMS script with properly escaped arguments
                    $escaped_message = escapeshellarg($message);
                    $escaped_phone = escapeshellarg($phone_number);
                    
                    $command = "python ./py/smsnotif.py {$escaped_phone} {$escaped_message}";
                    $output = shell_exec($command);
                    
                    // Log the attempt
                    error_log("Sending SMS to {$phone_number} for student {$row['surname']}, {$row['first_name']}");
                    error_log("Message content: " . $message);
                    error_log("Command output: {$output}");
                    
                    if (stripos($output, 'success') !== false) {
                        $notification_count++;
                    } else {
                        $error_count++;
                    }

                    // Add delay between messages to prevent overwhelming the modem
                    sleep(10);  // 10-second delay between messages
                }

                // Mark notifications as sent for today
                file_put_contents($notifications_sent_file, $current_date);
                
                echo json_encode([
                    'success' => true,
                    'message' => "Batch notifications completed. Successful: $notification_count, Failed: $error_count"
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'No attendance records found for today'
                ]);
            }
        } catch (Exception $e) {
            error_log("Batch notification error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Not enough inactivity time has passed or notifications already sent today'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No activity timestamp found'
    ]);
}
?>