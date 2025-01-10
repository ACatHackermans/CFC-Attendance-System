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

    $username = "";

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $username .= htmlspecialchars($row['username']);
        }
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
            case 'present':
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
          height: 100%;
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
      }

      .avatar {
          background-color: #d9d9d9;
          border-radius: 50%;
          width: 50px;
          height: 50px;
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
          padding: 11px 8px;
          font: 500 15px/18px REM, sans-serif;
          color: rgb(36, 36, 36);
          font-weight: 700;
          height: 270px;
          display: flex;
          align-items: center;
          gap: 100px;
          justify-content: center;
      }

      .welcome {
          display: flex;
          flex-direction: column;
          justify-content: center;
          align-items: center;
          font-size: 20px;
          gap: 15px;
      }

      .user-avatar {
          background-color: #d9d9d9;
          border-radius: 50%;
          width: 150px;
          height: 150px;
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
          border: 1px solid #ccc;
          border-radius: 10px;
          padding: 20px;
          background: #fff;
      }

      .calendar h2 {
          text-align: center;
          margin-bottom: 20px;
          color: #1C7600;
      }

      .calendar-grid {
          display: grid;
          grid-template-columns: repeat(7, 1fr);
          gap: 10px;
          text-align: center;
      }

      .day {
          padding: 1px;
          border: 1px solid #ddd;
          border-radius: 5px;
      }

      .today {
          background-color: #14AE5C;
          color: white;
          font-weight: bold;
      }

      .day-name {
          font-weight: bold;
          text-transform: uppercase;
          color: #333;
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
          min-height: 200px;
      }

      .status-column {
          display: flex;
          flex-direction: column;
          padding: 15px;
          border-radius: 10px;
          min-height: 150px;
          color: #000;
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

      h3, h4 {
          color: #1C7600;
          font-weight: 700;
          margin-bottom: 15px;
          font-family: "Rem-Regular", sans-serif;
          align-self: center;
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
                        <a href="attendancelog.php" class="nav-item">
                            <img src="res\icons\pie-graph.svg" alt="" class="nav-icon" />
                            <span>Student Attendance Log History</span>
                        </a>
                        <a href="attendancelogin.php" class="nav-item">
                            <img src="res\icons\card.svg" alt="" class="nav-icon" />
                            <span>Student Attendance Login & NFC Enrollment</span>
                        </a>
                    </div>

                    <div class="user-section">
                        <div class="user-profile">
                            <div class="avatar" role="img" aria-label="User avatar"></div>
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
                                <div class="user-avatar" role="img" aria-label="User avatar"></div>
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
                            if ($row['status_today'] == 'present') {
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
                                <h3>Attendance Today</h3>
                                <span class="percentage"><?php echo $attendance_percentage; ?>%</span>
                            </div>
                            <ul class="attendance-list">
                                <?php
                                if (!empty($today_attendance)) {
                                    foreach ($today_attendance as $student) {
                                        $time_display = date('h:i A', strtotime($student['time_in']));
                                        $status_class = $student['status_today'] === 'present' ? 'status-ontime' : 'status-late';
                                        echo '<li class="attendance-item">
                                                <div class="student-avatar" role="img" aria-label="Student avatar"></div>
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
                                <h4>On-time (<?php echo count($ontime_students); ?>)</h4>
                                <div class="status-list">
                                    <?php
                                    if (!empty($ontime_students)) {
                                        foreach ($ontime_students as $student) {
                                            $total_ontime = $student['on_time'];
                                            echo '<div class="status-item">' . 
                                                htmlspecialchars($student['surname']) .
                                                '<br><small>Total On-time: ' . $total_ontime . '</small></div>';
                                        }
                                    } else {
                                        echo '<div class="status-item">No on-time students</div>';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="status-column status-late">
                                <h4>Lates (<?php echo count($late_students); ?>)</h4>
                                <div class="status-list">
                                    <?php
                                    if (!empty($late_students)) {
                                        foreach ($late_students as $student) {
                                            $total_lates = $student['lates'];
                                            echo '<div class="status-item">' . 
                                                htmlspecialchars($student['surname']) .
                                                '<br><small>Total Lates: ' . $total_lates . '</small></div>';
                                        }
                                    } else {
                                        echo '<div class="status-item">No late students</div>';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="status-column status-absent">
                                <h4>Absentees (<?php echo count($absent_students); ?>)</h4>
                                <div class="status-list">
                                    <?php
                                    if (!empty($absent_students)) {
                                        foreach ($absent_students as $student) {
                                            echo '<div class="status-item">' . 
                                                htmlspecialchars($student['surname']) . '</div>';
                                        }
                                    } else {
                                        echo '<div class="status-item">No absent students</div>';
                                    }
                                    ?>
                                </div>
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
</body>
</html>