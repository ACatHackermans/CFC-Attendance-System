<?php 
session_start();

require("connection.php");

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $sql = "SELECT username FROM users WHERE user_id = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $username = htmlspecialchars($row['username']);
    } else {
        $username = "No record found.";
    }

    $stmt->close();
} else {
    header("Location: ./login.php");
    die;
}

// Get attendance data and calculate percentages
$attendance_sql = "SELECT ar.*, cl.nfc_uid 
                FROM attendance_report ar 
                JOIN class_list cl ON ar.student_num = cl.student_num";
$attendance_result = $con->query($attendance_sql);

// Get total number of students
$total_sql = "SELECT COUNT(*) as total FROM class_list";
$total_result = $con->query($total_sql);
$total_students = $total_result->fetch_assoc()['total'];

// Initialize arrays and counters
$ontime_students = [];
$late_students = [];
$absent_students = [];
$today_attendance = [];
$ontime_count = 0;

// Process attendance data
if ($attendance_result->num_rows > 0) {
    while ($row = $attendance_result->fetch_assoc()) {
        switch ($row['status_today']) {
            case 'on time':
                $ontime_students[] = $row;
                $today_attendance[] = $row;
                $ontime_count++;
                break;
            case 'late':
                $late_students[] = $row;
                $today_attendance[] = $row;
                break;
            case 'absent':
                $absent_students[] = $row;
                break;
        }
    }
}

// Calculate attendance percentage
$attendance_percentage = $total_students > 0 ? round(($ontime_count / $total_students) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Home Dashboard - CFCSR Student Attendance Management System</title>
    <link rel="icon" type="image/x-icon" href="./res/img/favicon.ico">
    <script src="./js/jquery-3.7.1.min.js"></script>

    <style>
    /* Base styles */
    html, body {
        margin: 0;
        padding: 0;
        width: 100%;
        height: 100%;
        position: relative;
    }

    /* Layout components */
    .bottom-outline {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        box-shadow: 0 0 4px 3px rgb(11, 158, 0);
    }

    .page-wrapper {
        box-sizing: border-box;
        width: 100%;
        height: 100%;
        margin: 0;
    }

    .main-layout {
        display: flex;
        gap: 0px;
        margin: auto;
    }

    /* Sidebar styles */
    .sidebar-column {
        display: flex;
        flex-direction: column;
        line-height: normal;
        width: 25%;
        height: 100%;
        background-color: #f0f0f0;
        position: sticky;
        top: 0;
    }

    .sidebar {
        display: flex;
        flex-grow: 1;
        flex-direction: column;
        font: 400 16px/1.5 Roboto, sans-serif;
        padding: 15px;
        height: 100vh;
    }

    .logo-image {
        object-fit: contain;
        width: 150px;
        align-self: center;
    }

    .system-title {
        color: #1C7600;
        font-size: 16px;
        font-weight: 700;
        line-height: 20px;
        text-align: center;
        text-transform: uppercase;
        margin-top: 18px;
    }

    /* Navigation styles */
    .nav-tabs {
        display: flex;
        margin-top: 10px;
        flex-direction: column;
        justify-content: center;
        gap: 3px;
    }

    .nav-item {
        border-radius: 10px;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 15px;
        color: #343434;
        font-weight: 700;
        letter-spacing: -0.2px;
        text-decoration: none;
    }

    .nav-item.active {
        background-color: #098100;
        color: #fff;
    }

    .nav-item.active .nav-icon {
        filter: brightness(0) invert(1);
    }

    .nav-item:hover {
        background-color: rgb(11, 158, 0);
        color: #fff;
    }

    .nav-item:hover .nav-icon {
        filter: brightness(0) invert(1);
    }

    .nav-icon {
        aspect-ratio: 1;
        object-fit: contain;
        width: 24px;
    }

    /* User section styles */
    .user-section {
        display: flex;
        margin-top: 50px;
        flex-direction: column;
        justify-content: center;
    }

    .user-profile {
        display: flex;
        align-items: center;
        gap: 9px;
        font-size: 20px;
        color: #000;
        letter-spacing: -0.2px;
        padding: 32px;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .user-profile:hover {
        background-color: rgba(0, 0, 0, 0.05);
        border-radius: 10px;
    }

    .avatar {
        background-color: #d9d9d9;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        flex-shrink: 0;
        border: 2px solid #14AE5C;
    }

    /* Content area styles */
    .content-column {
        display: flex;
        flex-direction: column;
        width: 100%;
        overflow: hidden;
        flex-grow: 1;
        height: 100%;
        padding-top: 120px;
    }

    .content-wrapper {
        width: 100%;
        overflow-y: auto;
        flex-grow: 1;
    }

    .page-header {
        text-shadow: 0 4px 4px rgba(0, 0, 0, 0.25);
        border-radius: 5px;
        box-shadow: 0 6px 4px rgba(0, 0, 0, 0.25);
        background: linear-gradient(
            95.19deg,
            rgba(20, 174, 92, 1) 0%,
            rgba(252, 238, 28, 1) 100%
        );
        color: #fff;
        padding: 47px 31px 16px;
        font: 600 40px/1.3 REM, sans-serif;
        position: fixed;
        z-index: 1000;
        top: 0;
        width: 100%;
    }

    /* Top section styles */
    .top-section {
        display: flex;
        align-items: flex-start;
        justify-content: center;
        margin: 15px;
        gap: 15px;
        color: #000000;
        text-align: center;
        font-family: "Rem-Regular", sans-serif;
    }

    .welcome-and-events {
        width: 60%;
        border-radius: 10px;
        background: linear-gradient(
            147.59deg,
            rgba(149, 196, 148, 1) 0%,
            rgba(252, 238, 28, 1) 100%
        );
        padding: 20px;
        font: 500 15px/18px REM, sans-serif;
        color: rgb(36, 36, 36);
        font-weight: 700;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 30px;
        height: 100%; /* Match parent height */
        min-height: 300px; /* Match calendar's typical height */
    }

    .welcome {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        font-size: 20px;
        gap: 15px;
        flex: 1;
        text-align: center;
    }

    .attendance-chart {
        flex: 1;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .attendance-chart h3 {
        margin-bottom: 15px;
    }

    .attendance-chart img {
        max-width: 180px;
        height: auto;
        display: block;
    }

    .user-avatar {
        background-color: #d9d9d9;
        border-radius: 50%;
        width: 150px;
        height: 150px;
        border: 3px solid #14AE5C;
        object-fit: contain;
        background-position: center;
        background-size: cover;
    }

    .events {
        font-size: 20px;
        line-height: 130%;
        font-weight: 400;
    }

    .events-list {
        list-style: none;
        padding: 0;
        text-align: left;
    }

    .event-item {
        margin: 10px 0;
        cursor: pointer;
    }

    /* Calendar styles */
    .calendar {
        border: none;
        border-radius: 10px;
        padding: 20px;
        background: linear-gradient(
            147.59deg,
            rgba(149, 196, 148, 0.3) 0%,
            rgba(252, 238, 28, 0.3) 100%
        );
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        height: 300px;
        display: flex;
        flex-direction: column;
    }

    .calendar h2 {
        text-align: center;
        margin-bottom: 20px;
        color: #1C7600;
        font-weight: 700;
        font-family: "Rem-Regular", sans-serif;
    }

    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 5px;
    }

    .day {
        padding: 8px;
        border: none;
        border-radius: 5px;
        transition: all 0.2s ease;
        background: rgba(255, 255, 255, 0.5);
        text-align: center;
        font-weight: 500;
    }

    .day-name {
        font-weight: bold;
        color: #1C7600;
        background: none;
        padding: 8px;
    }

    .today {
        background-color: #14AE5C;
        color: white;
        font-weight: bold;
    }

    /* Bottom section styles */
    .bottom-section {
        display: flex;
        flex-direction: row;
        gap: 20px;
        margin: 20px;
        padding: 25px;
        background-color: white;
        border-radius: 6px;
        box-shadow: 0 10px 4px rgba(0, 0, 0, 0.25);
        border: 1px solid #fff;
        font-family: "Rem-Regular", sans-serif;
    }

    .attendance-section {
        flex: 1;
        min-width: 250px;
        max-width: 300px;
        padding: 15px;
        background: linear-gradient(147.59deg, rgba(149, 196, 148, 0.3) 0%, rgba(252, 238, 28, 0.3) 100%);
        border-radius: 6px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .attendance-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .percentage {
        background: rgba(255, 255, 255, 0.8);
        padding: 5px 10px;
        border-radius: 15px;
        font-weight: bold;
        color: #1C7600;
        font-size: 0.9em;
    }

    .attendance-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .attendance-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 10px;
        border-bottom: 1px solid rgba(20, 174, 92, 0.2);
    }

    .attendance-item:last-child {
        border-bottom: none;
    }

    .student-avatar {
        background-color: #d9d9d9;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        flex-shrink: 0;
    }

    .status-columns {
        flex: 2;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
    }

    .status-column {
        display: flex;
        flex-direction: column;
        padding: 15px;
        border-radius: 10px;
        background: linear-gradient(147.59deg, rgba(149, 196, 148, 0.3) 0%, rgba(252, 238, 28, 0.3) 100%);
    }

    .status-column h4 {
        color: #1C7600;
        font-weight: 700;
        margin-bottom: 15px;
        font-family: "Rem-Regular", sans-serif;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 0 10px;
    }

    .status-column h4 .percentage {
        font-size: 0.8em;
        padding: 2px 6px;
        margin-left; auto;
    }

    .status-column .attendance-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .status-column .attendance-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 10px;
        border-bottom: 1px solid rgba(20, 174, 92, 0.2);
        background-color: rgba(255, 255, 255, 0.5);
        margin-bottom: 8px;
        border-radius: 8px;
    }

    .status-column .attendance-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }

    .status-column .student-info {
        flex: 1;
    }

    .status-column .count-info {
        font-size: 0.9em;
        color: #666;
        margin-top: 2px;
    }

    .status-column.status-ontime,
    .status-column.status-late,
    .status-column.status-absent {
        /* background: linear-gradient(147.59deg, rgba(149, 196, 148, 1) 0%, rgba(252, 238, 28, 1) 100%); */
        background: linear-gradient(147.59deg, rgba(149, 196, 148, 0.3) 0%, rgba(252, 238, 28, 0.3) 100%);
    }

    /* .status-column.status-absent {
        background-color: #e3e3e3;
    } */

    .status-list {
        margin-top: 10px;
        overflow-y: auto;
        max-height: 150px;
        align-self: center;
    }

    .status-item {
        padding: 8px;
        margin: 5px 0;
        background-color: rgba(255, 255, 255, 0.5);
        border-radius: 4px;
        font-weight: 500;
    }

    .status-indicator {
        display: inline-block;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        margin-right: 8px;
        vertical-align: middle;
    }

    .status-indicator.ontime {
        background-color: #14AE5C;
    }

    .status-indicator.late {
        background-color: #FCEE1C;
    }

    .status-indicator.absent {
        background-color: #DC3545;
    }

    .status-indicator.attendance {
        background: linear-gradient(147.59deg, #14AE5C 0%, #FCEE1C 100%);
    }

    h3, h4 {
        font-weight: 700;
        margin-bottom: 15px;
        font-family: "Rem-Regular", sans-serif;
        align-self: center;
    }

    /* Profile Modal styles */
    .profile-modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
    }

    .profile-modal-content {
        background-color: #fff;
        margin: 10% auto;
        padding: 30px;
        width: 300px; /* Reduced from 350px */
        border-radius: 15px;
        position: relative;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    }

    .profile-form {
        display: flex;
        flex-direction: column;
        gap: 20px;
        align-items: center;
    }

    .avatar-upload {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 15px;
        position: relative;
    }

    .avatar-preview {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background-color: #f0f0f0;
        overflow: hidden;
        position: relative;
        border: 3px solid #14AE5C;
    }

    .avatar-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .avatar-edit {
        position: absolute;
        right: -5px;
        bottom: -5px;
        width: 32px;
        height: 32px;
        background-color: #14AE5C;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background-color 0.2s;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        padding: 7px;
        border: 2px solid white;
    }

    .avatar-edit:hover {
        background-color: #098100;
    }

    .avatar-edit img {
        width: 15px;
        height: 15px;
        filter: brightness(0) invert(1);
    }

    .avatar-input {
        display: none;
    }

    .close-modal {
        position: absolute;
        right: 15px;
        top: 15px;
        cursor: pointer;
        font-size: 24px;
        color: #666;
        transition: color 0.2s;
    }

    .close-modal:hover {
        color: #000;
    }

    .username-group {
        width: 100%;
        margin-top: 10px;
    }

    .username-group label {
        display: block;
        margin-bottom: 8px;
        color: #333;
        font-weight: 500;
    }

    .username-group input {
        width: 100%;
        box-sizing: border-box; /* This ensures padding doesn't add to width */
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 14px;
        transition: border-color 0.2s;
    }

    .username-group input:focus {
        outline: none;
        border-color: #14AE5C;
    }

    .save-button {
        background-color: #14AE5C;
        color: white;
        border: none;
        border-radius: 8px;
        padding: 12px 25px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.2s;
        width: 100%;
    }

    .save-button:hover {
        background-color: #098100;
    }

    .save-button:disabled {
        background-color: #ccc;
        cursor: not-allowed;
    }

    /* Responsive styles */
    @media (max-width: 1024px) {
        .top-section {
            flex-direction: column;
            align-items: center;
        }

        .welcome-and-events {
            width: 100%;
            height: auto;
            flex-direction: column;
            gap: 20px;
            padding: 20px;
        }

        .calendar {
            width: 100%;
        }

        .bottom-section {
            flex-direction: column;
            align-items: stretch;
        }

        .attendance-section {
            max-width: none;
        }

        .status-columns {
                grid-template-columns: 1fr;
            }
            
            .status-column {
                margin-bottom: 15px;
            }
        }

    @media (max-width: 768px) {
        .bottom-section {
            margin: 10px;
            padding: 15px;
        }

        .attendance-item {
            flex-direction: column;
            text-align: center;
        }

        .status-columns {
            gap: 10px;
        }
    }
    </style>
</head>

<body>
<main class="page-wrapper">
        <div class="main-layout">
            <aside class="sidebar-column">
                <nav class="sidebar">
                    <img src="res\img\CFC Logo.svg" alt="System Logo" class="logo-image" />
                    <h1 class="system-title">Student Attendance Management System</h1>
                    
                    <div class="nav-tabs">
                        <a href="dashboard.php" class="nav-item active">
                            <img src="res\icons\home.svg" alt="" class="nav-icon" />
                            <span>Home</span>
                        </a>
                        <a href="classlist.php" class="nav-item">
                            <img src="res\icons\users.svg" alt="" class="nav-icon" />
                            <span>Class List</span>
                        </a>
                        <a href="attendancereport.php" class="nav-item">
                            <img src="res\icons\pie-graph.svg" alt="" class="nav-icon" />
                            <span>Student Attendance Report</span>
                        </a>
                        <a href="attendancelogin.php" class="nav-item">
                            <img src="res\icons\card.svg" alt="" class="nav-icon" />
                            <span>Student Attendance Login & NFC Enrollment</span>
                        </a>
                    </div>

                    <div class="user-section">
                        <div class="user-profile">
                            <div class="avatar" style="background-image: url('get_avatar.php?id=<?php echo $user_id; ?>'); background-size: cover; background-position: center;" role="img" aria-label="User avatar"></div>
                            <span class="username"><?php echo $username; ?></span>
                        </div>
                        <a class="nav-item" href="logout.php">
                            <img src="res\icons\logout.svg" alt="" class="nav-icon" />
                            Logout
                        </a>
                    </div>
                </nav>
            </aside>

            <section class="content-column">
                <header class="page-header">DASHBOARD</header>

                <div class="content-wrapper">            
                    <div class="top-section">
                        <div class="welcome-and-events">
                            <div class="welcome">
                                Welcome, <?php echo $username; ?>!
                                <div class="user-avatar" style="background-image: url('get_avatar.php?id=<?php echo $user_id; ?>'); background-size: cover; background-position: center;" role="img" aria-label="User avatar"></div>
                            </div>
                            <div class="attendance-chart">
                                <?php
                                $ontime = count($ontime_students);
                                $late = count($late_students);
                                $absent = count($absent_students);
                                ?>
                                Total Attendance Graph
                                <img src="generate_chart.php?ontime=<?php echo $ontime; ?>&late=<?php echo $late; ?>&absent=<?php echo $absent; ?>" 
                                    alt="Attendance Chart" 
                                    style="max-width: 300px; height: auto;" />
                            </div>
                        </div>
                        <div class="calendar">
                            <h2 id="month-year"></h2>
                            <div class="calendar-grid" id="calendar-grid">
                            </div>
                        </div>
                    </div>    
                    <?php
                    // Get today's date
                    $today = date('Y-m-d');

                    // Query for total students
                    $total_query = "SELECT COUNT(*) as total FROM class_list";
                    $total_result = $con->query($total_query);
                    $total_students = $total_result->fetch_assoc()['total'];

                    // Query for today's attendance with detailed status
                    $attendance_query = "SELECT 
                        ar.student_num,
                        ar.surname,
                        ar.first_name,
                        ar.status_today,
                        ar.time_in,
                        ar.on_time,
                        ar.lates,
                        ar.absences
                        FROM attendance_report ar
                        WHERE DATE(ar.time_in) = CURRENT_DATE()
                        ORDER BY ar.time_in DESC";

                    $attendance_result = $con->query($attendance_query);

                    // Initialize arrays for different status categories
                    $ontime_students = [];
                    $late_students = [];
                    $absent_students = [];
                    $today_attendance = [];

                    // Get all students who haven't logged in today (absences)
                    $absent_query = "SELECT cl.student_num, cl.surname, cl.first_name 
                                    FROM class_list cl 
                                    LEFT JOIN attendance_report ar 
                                    ON cl.student_num = ar.student_num 
                                    AND DATE(ar.time_in) = CURRENT_DATE()
                                    WHERE ar.student_num IS NULL";
                    $absent_result = $con->query($absent_query);

                    // Process attendance records
                    $ontime_count = 0;
                    if ($attendance_result && $attendance_result->num_rows > 0) {
                        while ($row = $attendance_result->fetch_assoc()) {
                            // Add to today's attendance list
                            $today_attendance[] = $row;
                            
                            // Categorize by status
                            if ($row['status_today'] == 'on time') {
                                $ontime_students[] = $row;
                                $ontime_count++;
                            } elseif ($row['status_today'] == 'late') {
                                $late_students[] = $row;
                            }
                        }
                    }

                    // Add absent students to the absent list
                    if ($absent_result && $absent_result->num_rows > 0) {
                        while ($row = $absent_result->fetch_assoc()) {
                            $absent_students[] = $row;
                        }
                    }

                    // Calculate attendance percentage
                    $attendance_percentage = $total_students > 0 ? 
                        round((($ontime_count + count($late_students)) / $total_students) * 100, 1) : 0;
                    ?>

                    <div class="bottom-section">
                        <div class="attendance-section">
                        <div class="attendance-header">
                            <h3>
                                Attendance Today (<?php echo count($today_attendance); ?>)
                            </h3>
                            <span class="percentage"><?php echo $attendance_percentage; ?>%</span>
                        </div>
                            <ul class="attendance-list">
                                <?php
                                if (!empty($today_attendance)) {
                                    foreach ($today_attendance as $student) {
                                        $time_display = date('h:i A', strtotime($student['time_in']));
                                        $status_class = $student['status_today'] === 'on time' ? 'status-ontime' : 'status-late';
                                        echo '<li class="attendance-item">
                                                <div class="student-avatar" style="background-image: url(\'get_student_avatar.php?student_num=' . htmlspecialchars($student['student_num']) . '\'); background-size: cover; background-position: center;" role="img" aria-label="Student avatar"></div>
                                                <div class="student-info">
                                                    <div>' . htmlspecialchars($student['surname']) . ', ' . 
                                                    htmlspecialchars($student['first_name']) . '</div>
                                                    <div class="time ' . $status_class . '">
                                                        (' . $time_display . ' - ' . ucfirst($student['status_today']) . ')
                                                    </div>
                                                </div>
                                            </li>';
                                    }
                                } else {
                                    echo '<li class="attendance-item">No attendance records for today</li>';
                                }
                                ?>
                            </ul>
                        </div>

                        <div class="status-columns">
                            <div class="status-column status-ontime">
                            <h4>
                                <span class="status-indicator ontime"></span>
                                On-time (<?php echo count($ontime_students); ?>)
                                <span class="percentage"><?php echo $total_students > 0 ? round((count($ontime_students) / $total_students) * 100, 1) : 0; ?>%</span>
                            </h4>
                                <ul class="attendance-list">
                                    <?php
                                    if (!empty($ontime_students)) {
                                        foreach ($ontime_students as $student) {
                                            echo '<li class="attendance-item">
                                                    <!-- <div class="student-avatar" role="img" aria-label="Student avatar"></div> --> ○
                                                    <div class="student-info">
                                                        <div>' . htmlspecialchars($student['surname']) . ', ' . 
                                                        htmlspecialchars($student['first_name']) . '</div>
                                                    <!--    <div class="count-info">Total On-time: ' . $student['on_time'] . '</div> -->
                                                    </div>
                                                </li>';
                                        }
                                    } else {
                                        echo '<li class="attendance-item">No on-time students</li>';
                                    }
                                    ?>
                                </ul>
                            </div>
                            
                            <div class="status-column status-late">
                            <h4>
                                <span class="status-indicator late"></span>
                                Lates (<?php echo count($late_students); ?>)
                                <span class="percentage"><?php echo $total_students > 0 ? round((count($late_students) / $total_students) * 100, 1) : 0; ?>%</span>
                            </h4>
                                <ul class="attendance-list">
                                    <?php
                                    if (!empty($late_students)) {
                                        foreach ($late_students as $student) {
                                            echo '<li class="attendance-item">
                                                    <!--    <div class="student-avatar" role="img" aria-label="Student avatar"></div> --> ○
                                                    <div class="student-info">
                                                        <div>' . htmlspecialchars($student['surname']) . ', ' . 
                                                        htmlspecialchars($student['first_name']) . '</div>
                                                    <!--    <div class="count-info">Total Lates: ' . $student['lates'] . '</div> -->
                                                    </div>
                                                </li>';
                                        }
                                    } else {
                                        echo '<li class="attendance-item">No late students</li>';
                                    }
                                    ?>
                                </ul>
                            </div>
                            
                            <div class="status-column status-absent">
                            <h4>
                                <span class="status-indicator absent"></span>
                                Absentees (<?php echo count($absent_students); ?>)
                                <span class="percentage"><?php echo $total_students > 0 ? round((count($absent_students) / $total_students) * 100, 1) : 0; ?>%</span>
                            </h4>
                                <ul class="attendance-list">
                                    <?php
                                    if (!empty($absent_students)) {
                                        foreach ($absent_students as $student) {
                                            echo '<li class="attendance-item">
                                                    <!-- <div class="student-avatar" role="img" aria-label="Student avatar"></div> --> ○
                                                    <div class="student-info">
                                                        <div>' . htmlspecialchars($student['surname']) . ', ' . 
                                                        htmlspecialchars($student['first_name']) . '</div>
                                                    </div>
                                                </li>';
                                        }
                                    } else {
                                        echo '<li class="attendance-item">No absent students</li>';
                                    }
                                    ?>
                                </ul>
                            </div>
                        </div>
                    </div>          
                </div>
            </section>
        </div>
        <div class="bottom-outline"></div>
    </main>

    <script>
    function generateCalendar() {
        const calendarGrid = document.getElementById('calendar-grid');
        const monthYear = document.getElementById('month-year');

        const now = new Date();
        const year = now.getFullYear();
        const month = now.getMonth();
        const today = now.getDate();

        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const firstDay = new Date(year, month, 1).getDay();

        const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        // Clear calendar grid
        calendarGrid.innerHTML = '';

        // Display the current month and year
        monthYear.textContent = now.toLocaleString('default', {
            month: 'long',
            year: 'numeric',
        });

        // Add day names
        daysOfWeek.forEach(day => {
            const dayElement = document.createElement('div');
            dayElement.textContent = day;
            dayElement.classList.add('day', 'day-name');
            calendarGrid.appendChild(dayElement);
        });

        // Add empty boxes before the first day of the month
        for (let i = 0; i < firstDay; i++) {
            const emptyCell = document.createElement('div');
            calendarGrid.appendChild(emptyCell);
        }

        // Add days of the month
        for (let day = 1; day <= daysInMonth; day++) {
            const dayElement = document.createElement('div');
            dayElement.textContent = day;
            dayElement.classList.add('day');
            if (day === today) {
                dayElement.classList.add('today');
            }
            calendarGrid.appendChild(dayElement);
        }
    }

    // Generate the calendar when the page loads
    document.addEventListener('DOMContentLoaded', generateCalendar);
    </script>

    <!-- Profile Modal -->
    <div id="profileModal" class="profile-modal">
        <div class="profile-modal-content">
            <span class="close-modal">&times;</span>
            <form id="profileForm" class="profile-form">
                <div class="avatar-upload">
                    <div class="avatar-preview">
                        <img src="get_avatar.php?id=<?php echo $user_id; ?>" alt="Profile Avatar">
                    </div>
                    <label for="avatarInput" class="avatar-edit">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>
                        </svg>
                    </label>
                    <input type="file" id="avatarInput" name="avatar" accept="image/*" class="avatar-input">
                </div>
                <div class="username-group">
                    <label for="usernameInput">Username</label>
                    <input type="text" id="usernameInput" name="username" value="<?php echo $username; ?>">
                </div>
                <button type="submit" class="save-button">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Profile modal elements
        const modal = $('#profileModal');
        const modalContent = $('.profile-modal-content');
        const closeBtn = $('.close-modal');
        const userProfileDiv = $('.user-profile');
        const avatarPreview = $('.avatar-preview img');
        const avatarInput = $('#avatarInput');
        const profileForm = $('#profileForm');
        const usernameInput = $('#usernameInput');

        // Open modal when clicking on user profile
        userProfileDiv.on('click', function() {
            modal.fadeIn(300);
        });

        // Close modal when clicking close button
        closeBtn.on('click', function() {
            modal.fadeOut(300);
        });

        // Close modal when clicking outside
        modal.on('click', function(e) {
            if ($(e.target).is(modal)) {
                modal.fadeOut(300);
            }
        });

        // Preview image when selected
        avatarInput.on('change', function(e) {
            const file = this.files[0];
            if (file) {
                // Validate file type
                if (!file.type.match('image.*')) {
                    alert('Please select an image file.');
                    return;
                }

                // Validate file size (5MB max)
                if (file.size > 5242880) {
                    alert('File is too large. Maximum size is 5MB.');
                    return;
                }

                // Preview the image
                const reader = new FileReader();
                reader.onload = function(e) {
                    avatarPreview.attr('src', e.target.result);
                };
                reader.readAsDataURL(file);
            }
        });

        // Handle form submission
        profileForm.on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            const newUsername = usernameInput.val().trim();
            
            if (newUsername !== userProfileDiv.find('.username').text().trim()) {
                formData.append('username', newUsername);
            }
            
            const avatarFile = avatarInput[0].files[0];
            if (avatarFile) {
                formData.append('avatar', avatarFile);
            }
            
            if (formData.entries().next().done) {
                alert('No changes made.');
                return;
            }

            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.text();
            submitBtn.prop('disabled', true).text('Saving...');

            $.ajax({
                url: 'update_profile.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        if (response.username) {
                            userProfileDiv.find('.username').text(response.username);
                        }
                        
                        if (response.avatar_url) {
                            const avatarImg = `${response.avatar_url}`;
                            userProfileDiv.find('.avatar').css('background-image', `url(${avatarImg})`);
                            avatarPreview.attr('src', avatarImg);
                        }
                        
                        alert('Profile updated successfully!');
                        modal.fadeOut(300);
                    } else {
                        alert(response.error || 'Failed to update profile.');
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                },
                complete: function() {
                    submitBtn.prop('disabled', false).text(originalText);
                }
            });
        });
    });
    </script>
</body>
</html>