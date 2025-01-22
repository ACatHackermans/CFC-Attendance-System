<?php
header('Content-Type: application/json');
require("connection.php");

$json_str = file_get_contents('php://input');
$data = json_decode($json_str, true);

if (!isset($data['uid'])) {
    echo json_encode([
        'success' => false,
        'error' => 'No UID provided'
    ]);
    exit;
}

$nfc_uid = $data['uid'];

try {
    // Start transaction
    $con->begin_transaction();

    // Get student details from class_list
    $stmt = $con->prepare("SELECT student_num, surname, first_name, guardian_num, guardian_name FROM class_list WHERE nfc_uid = ?");
    $stmt->bind_param("s", $nfc_uid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Student not found');
    }

    $student = $result->fetch_assoc();
    $current_time = date('H:i:s');
    $current_date = date('Y-m-d');
    
    // Determine status based on time
    $status = (strtotime($current_time) > strtotime('08:00:00')) ? 'late' : 'on time';

    // Check if student already has an attendance record for today
    $check_stmt = $con->prepare("SELECT log_id FROM attendance_log WHERE student_number = ? AND log_date = ?");
    $check_stmt->bind_param("ss", $student['student_num'], $current_date);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        throw new Exception('Attendance already recorded for today');
    }

    // Insert attendance record in attendance_log
    $insert_stmt = $con->prepare("
        INSERT INTO attendance_log 
        (student_number, surname, name, log_date, time_in, status, guardian_num) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $insert_stmt->bind_param(
        "sssssss",
        $student['student_num'],
        $student['surname'],
        $student['first_name'],
        $current_date,
        $current_time,
        $status,
        $student['guardian_num']
    );

    if (!$insert_stmt->execute()) {
        throw new Exception('Failed to record attendance');
    }

    // Check if student exists in attendance_report
    $check_report_stmt = $con->prepare("
        SELECT report_id, on_time, lates, absences 
        FROM attendance_report 
        WHERE student_num = ?
    ");
    $check_report_stmt->bind_param("s", $student['student_num']);
    $check_report_stmt->execute();
    $report_result = $check_report_stmt->get_result();

    if ($report_result->num_rows > 0) {
        // Update existing record
        $report_data = $report_result->fetch_assoc();
        $on_time = $report_data['on_time'];
        $lates = $report_data['lates'];

        if ($status === 'on time') {
            $on_time++;
        } else {
            $lates++;
        }

        $update_report_stmt = $con->prepare("
            UPDATE attendance_report 
            SET status_today = ?, 
                on_time = ?, 
                lates = ?,
                time_in = CURRENT_TIMESTAMP
            WHERE student_num = ?
        ");
        $update_report_stmt->bind_param(
            "siis",
            $status,
            $on_time,
            $lates,
            $student['student_num']
        );
        $update_report_stmt->execute();
    } else {
        // Insert new record
        $on_time = ($status === 'on time') ? 1 : 0;
        $lates = ($status === 'late') ? 1 : 0;
        
        $insert_report_stmt = $con->prepare("
            INSERT INTO attendance_report 
            (student_num, surname, first_name, status_today, on_time, lates, absences, time_in)
            VALUES (?, ?, ?, ?, ?, ?, 0, CURRENT_TIMESTAMP)
        ");
        $insert_report_stmt->bind_param(
            "ssssii",
            $student['student_num'],
            $student['surname'],
            $student['first_name'],
            $status,
            $on_time,
            $lates
        );
        $insert_report_stmt->execute();
    }

    // Build message for notification queue
    $message = "Good day, {$student['guardian_name']},| |";
    $message .= "{$student['surname']}, {$student['first_name']} has successfully checked in at school today, ";
    $message .= date('F j, Y') . ", " . date('h:i A', strtotime($current_time)) . "|";
    $message .= "Status: " . ucfirst($status) . "| |";
    $message .= "- CFC School Administration";

    $notification_queued = false;
    // Comment out try catch to disable SMS notification
    // try {
    //     $queue_stmt = $con->prepare("
    //         INSERT INTO notification_queue 
    //         (student_num, guardian_phone, guardian_name, message) 
    //         VALUES (?, ?, ?, ?)
    //     ");
        
    //     $queue_stmt->bind_param(
    //         "ssss",
    //         $student['student_num'],
    //         $student['guardian_num'],
    //         $student['guardian_name'],
    //         $message
    //     );
        
    //     $notification_queued = $queue_stmt->execute();
    // } catch (Exception $e) {
    //     error_log("Failed to queue notification: " . $e->getMessage());
    // }

    $activity_file = "./res/last_attendance_activity.txt";
    file_put_contents($activity_file, time());

    $con->commit();

    echo json_encode([
        'success' => true,
        'student' => [
            'surname' => $student['surname'],
            'first_name' => $student['first_name'],
            'time_in' => date('h:i A', strtotime($current_time)),
            'status' => ucfirst($status),
            'notification_queued' => $notification_queued,
            'attendance_summary' => [
                'on_time' => $on_time,
                'lates' => $lates
            ]
        ]
    ]);

} catch (Exception $e) {
    $con->rollback();
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

if (isset($stmt)) $stmt->close();
if (isset($check_stmt)) $check_stmt->close();
if (isset($insert_stmt)) $insert_stmt->close();
if (isset($check_report_stmt)) $check_report_stmt->close();
if (isset($update_report_stmt)) $update_report_stmt->close();
if (isset($insert_report_stmt)) $insert_report_stmt->close();
if (isset($queue_stmt)) $queue_stmt->close();
$con->close();
?>