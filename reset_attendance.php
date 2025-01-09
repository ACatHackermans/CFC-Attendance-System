<?php
header('Content-Type: application/json');
require("connection.php");

try {
    // Start transaction
    $con->begin_transaction();

    // Get current time
    $current_time = date('H:i:s');
    $cutoff_time = '17:00:00'; // 5pm cutoff

    // Only proceed if it's past 5pm
    if ($current_time >= $cutoff_time) {
        // Get list of all students who haven't logged in today
        $absent_students_query = "
            SELECT cl.student_num, cl.surname, cl.first_name
            FROM class_list cl
            LEFT JOIN attendance_log al ON cl.student_num = al.student_number 
                AND DATE(al.log_date) = CURRENT_DATE
            WHERE al.student_number IS NULL";

        $absent_result = $con->query($absent_students_query);

        // Update absence count for students who didn't log in
        while ($absent_student = $absent_result->fetch_assoc()) {
            // Check if student exists in attendance_report
            $check_report = $con->prepare("
                SELECT absences 
                FROM attendance_report 
                WHERE student_num = ?");
            $check_report->bind_param("s", $absent_student['student_num']);
            $check_report->execute();
            $report_result = $check_report->get_result();

            if ($report_result->num_rows > 0) {
                // Update existing record
                $report_data = $report_result->fetch_assoc();
                $new_absences = $report_data['absences'] + 1;

                $update_stmt = $con->prepare("
                    UPDATE attendance_report 
                    SET absences = ?, 
                        status_today = 'absent'
                    WHERE student_num = ?");
                $update_stmt->bind_param("is", $new_absences, $absent_student['student_num']);
                $update_stmt->execute();
            } else {
                // Insert new record
                $insert_stmt = $con->prepare("
                    INSERT INTO attendance_report 
                    (student_num, surname, first_name, status_today, on_time, lates, absences)
                    VALUES (?, ?, ?, 'absent', 0, 0, 1)");
                $insert_stmt->bind_param(
                    "sss",
                    $absent_student['student_num'],
                    $absent_student['surname'],
                    $absent_student['first_name']
                );
                $insert_stmt->execute();
            }
        }

        // Only reset status if it's a new day
        if (date('H') >= 0 && date('H') < 8) { // Between 12am and 8am
            // Reset status_today to 'absent' for all students
            $reset_query = "UPDATE attendance_report SET status_today = 'absent', time_in = NULL";
            $result = $con->query($reset_query);

            if (!$result) {
                throw new Exception('Failed to reset attendance status');
            }
        }

        $con->commit();
        echo json_encode([
            'success' => true, 
            'message' => 'Attendance checked and absences updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Not yet cutoff time'
        ]);
    }

} catch (Exception $e) {
    $con->rollback();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$con->close();
?>