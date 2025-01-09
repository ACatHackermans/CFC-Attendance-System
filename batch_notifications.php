<?php
error_log("Batch notifications script started");

// Save as batch_notifications.php
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
                    $student_data = [
                        'guardian_name' => $row['guardian_name'],
                        'student_name' => $row['surname'] . ', ' . $row['first_name'],
                        'time_in' => date('h:i A', strtotime($row['time_in'])),
                        'status' => ucfirst($row['status_today']),
                        'guardian_num' => $row['guardian_num']
                    ];

                    // Prepare and send message
                    $message = "Dear {$student_data['guardian_name']}, This is to inform you that {$student_data['student_name']} has checked in at school today.\nDate: " . date('F j, Y') . "\nTime: {$student_data['time_in']}\nStatus: {$student_data['status']}\n\n- CFC School Administration";

                    // Send SMS
                    $escaped_message = escapeshellarg($message);
                    $phone_raw = $student_data['guardian_num'];
                    $phone_formatted = preg_replace('/^0|\+63/', '', $phone_raw); // Remove leading 0 or +63
                    $formatted_phone = "+63" . $phone_formatted;
                    error_log("Original phone: " . $phone_raw);
                    error_log("Formatted phone: " . $formatted_phone);
                    $escaped_phone = escapeshellarg($formatted_phone);
                    $command = "python ./py/smsnotif.py {$escaped_phone} {$escaped_message}";
                    error_log("Executing command: " . $command);
                    $output = shell_exec($command);
                    error_log("Command output: " . $output);
                    
                    if (strpos($output, 'Successfully') !== false) {
                        $notification_count++;
                    } else {
                        $error_count++;
                    }

                    // Add delay between messages to prevent overloading
                    sleep(2);
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