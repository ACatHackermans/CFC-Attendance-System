<?php 
session_start();

require("connection.php");

// Check if we need to run attendance validation (after 5pm)
$current_time = date('H:i:s');
$cutoff_time = '17:00:00'; // 5pm cutoff
$last_check_file = "./res/last_attendance_check.txt";
$current_date = date('Y-m-d');
$needs_check = false;

if (file_exists($last_check_file)) {
    $last_check = file_get_contents($last_check_file);
    if ($last_check !== $current_date && $current_time >= $cutoff_time) {
        $needs_check = true;
    }
} else {
    if ($current_time >= $cutoff_time) {
        $needs_check = true;
    }
}

if ($needs_check) {
    // Call reset_attendance.php via AJAX
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost/CFC-Attendance-System-main/reset_attendance.php");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    // Update last check date
    file_put_contents($last_check_file, $current_date);
}

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

    $sql = "SELECT student_num, surname, first_name, status_today, on_time, lates, absences, time_in 
            FROM attendance_report
            ORDER BY surname ASC";
    $result = $con->query($sql);

    $stmt->close();
    $con->close();
} else {
    header("Location: ./login.php");
    die;
}
?>

<!DOCTYPE html>

<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Attendance Reports - CFCSR Student Attendance Management System</title>
    <link rel="icon" type="image/x-icon" href="./res/img/favicon.ico">
    <script src="./js/jquery-3.7.1.min.js"></script>
    <script src="./js/search.js"></script>

    <style>
      html, body {
        margin: 0;
        padding: 0;
        width: 100%;
        height: 100%;
        position: relative;
      }
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
        /* max-width: 1920px; */
        margin: auto;
      }
      .sidebar-column {
        display: flex;
        flex-direction: column;
        line-height: normal;
        width: 25%;
        height: 100%;
        /* min-width: 250px;
        max-width: 300px; */
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
        /* aspect-ratio: 0.99; */
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
      .nav-tabs {
        display: flex;
        margin-top: 10px;
        /* min-height: 295px; */
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
        filter: brightness(0) invert(1); /* Turn icon white */
      }
      .nav-item:hover {
        background-color:rgb(11, 158, 0);
        color: #fff;
      }
      .nav-item:hover .nav-icon {
        filter: brightness(0) invert(1); /* Turn icon white */
      }
      .nav-icon {
        aspect-ratio: 1;
        object-fit: contain;
        width: 24px;
      }
      .user-section {
        display: flex;
        margin-top: 50px;
        /* min-height: 243px; */
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
      /* .username { width: 90px; } */
      .content-column {
        display: flex;
        flex-direction: column;
        width: 100%;
        overflow: hidden;
        flex-grow: 1;
        height: 100%;
        padding-top: 120px; /* Adjust based on the height of .page-header */
      }
      .content-wrapper { 
        width: 100%;
        overflow-y: auto;
        flex-grow: 1;
      }
      .page-header {
        text-shadow: 0 4px 4px rgba(0,0,0,0.25);
        border-radius: 5px;
        box-shadow: 0 6px 4px rgba(0,0,0,0.25);
        background: linear-gradient(
          95.19deg,
        rgba(20, 174, 92, 1) 0%,
        rgba(252, 238, 28, 1) 100%
        );
        color: #fff;
        padding: 47px 31px 16px;
        font: 600 40px/1.3 REM, sans-serif;
        position: fixed; /* Changed from sticky */
        z-index: 1000;
        top: 0;
        width: 100%; /* Ensure it spans the viewport width */
      }
      .controls-section {
        display: flex;
        /* max-width: 1920px; */
        align-items: center;
        flex-wrap: wrap;
        justify-content: space-between;
        margin: 15px;
      }
      .class-info {
        width: 300px;
        border-radius: 10px;
        background: linear-gradient(
          147.59deg,
          rgba(149, 196, 148, 1) 0%,
          rgba(252, 238, 28, 1) 100%
        );
        padding: 11px 8px 11px;
        font: 500 15px/18px REM, sans-serif;
        color: rgb(36, 36, 36);
        font-weight: 700;
      }
      .datetime-wrapper {
        border-radius: 6px;
        display: flex;
        min-height: 42px;
        align-items: center;
        gap: 6px;
        justify-content: flex-end;
      }
      .date-display,
      .time-display {
        border-radius: 6px;
        background-color: rgba(120, 120, 128, 0.12);
        padding: 8px 12px;
        font-family: "SF Pro", sans-serif;
        font-size: 16px;
        color: rgba(0, 153, 81, 1);
        text-align: center;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      }
      .button {
      background-color: #009951;
      border-radius: 8px;
      padding: 12px;
      align-items: center;
      text-decoration: none;
      color: #f5f5f5;
      font-family: var(
        --single-line-body-base-font-family,
        "Inter-Regular",
        sans-serif
      );
      }
      .button:hover {
        background-color: #12a054;
      }
      .search-controls {
        display: flex;
        margin: 0;
        gap: 15px;
      }
      .action-buttons {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 5px;
      }
      .search-box {
        border-radius: 9999px;
        background-color: #fff;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px 16px;
        border: 1px solid #d9d9d9;
      }
      .search-input {
        flex: 1;
        border: none;
        outline: none;
        background: transparent;
      }
      .search-icon {
        aspect-ratio: 1;
        width: 16px;
      }

      .table-container {
        margin: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        background: #fff;
      }

      .table-wrapper {
          width: 100%;
          overflow-x: auto;
          border-radius: 8px;
      }

      .data-table {
          width: 100%;
          border-collapse: collapse;
          background-color: #ffffff;
          color: #000000;
          font: 500 14px/1.4 Inter, sans-serif;
          min-width: 1000px; /* Ensures horizontal scroll on smaller screens */
      }

      .data-table thead {
          background: #f8f9fa;
          position: sticky;
          top: 0;
      }

      .data-table th {
          padding: 15px 20px;
          text-align: left;
          font-weight: 600;
          border-bottom: 2px solid #e9ecef;
          white-space: nowrap;
      }

      .data-table td {
          padding: 12px 20px;
          border-bottom: 1px solid #e9ecef;
          white-space: nowrap;
      }

      .data-table tr:hover {
          background-color: #f8f9fa;
      }

      /* Width for specific columns */
      .col-student-num { width: 140px; }
      .col-name { width: 150px; }
      .col-status { width: 120px; }
      .col-count { width: 100px; text-align: center; }
      .col-time { width: 100px; 
      }
      /* Status colors */
      .status-present { color: #198754; }
      .status-late { color: #ffc107; }
      .status-absent { color: #dc3545; }
      /* Center count columns */
      .count-cell {
          text-align: center;
      }
      /* .scroll-track {
        border-radius: 6px;
        background-color: #e2e2e2;
        align-self: end;
        margin-top: 52px;
        padding: 4px 0 535px;
      }
      .scroll-thumb {
        border-radius: 6px;
        background-color: #c4c4c4;
        height: 73px;
      } */
      
      @media (max-width: 991px) {
        .main-layout { 
          flex-direction: column;
          align-items: stretch;
          gap: 0;
        }
        .sidebar-column { width: 100%; }
        .nav-tabs {
          margin-top: 40px;
          padding: 0 20px;
        }
        .user-section {
          margin-top: 40px;
          padding: 0 20px;
        }
        .user-profile { padding: 0 20px; }
        .content-column { width: 100%; }
        .page-header {
          font-size: 40px;
          padding: 100px 20px 0;
        }
        .controls-section { margin-right: 10px; }
        /* .scroll-track {
          padding-bottom: 100px;
          margin-top: 40px;
        } */

        .date-year,
        .time-display {
          color: rgba(0, 153, 81, 1);
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
              <a href="dashboard.php" class="nav-item">
                <img src="res\icons\home.svg" alt="" class="nav-icon" />
                <span>Home</span>
              </a>
              <a href="classlist.php" class="nav-item">
                <img src="res\icons\users.svg" alt="" class="nav-icon" />
                <span>Class List</span>
              </a>
              <a href="attendancereport.php" class="nav-item active">
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
                <div class="avatar" role="img" aria-label="User avatar"></div>
                <span class="username"><?php echo $username; ?></span>
              </div>
              <a class="nav-item" href="logout.php" target="_parent" style="font-weight: 700;">
                <img src="res\icons\logout.svg" alt="" class="nav-icon" />
                Logout
              </a>
            </div>
          </nav>
        </aside>

        <section class="content-column">
          <header class="page-header">STUDENT ATTENDANCE REPORTS</header>

          <div class="content-wrapper">            
            <div class="controls-section">
              <div class="class-info">
                Grade: 12 
                <br />
                Section: Competitive
                <br />
                Strand: STEM
              </div>

              <div class="datetime-wrapper">
                <time class="date-display"></time>
                <time class="time-display"></time>
              </div>

              <a href="attendancehistory.php" class="button">
                <span>Attendance History</span>
              </a>
              
              <div class="search-controls">
                <form class="search-box" role="search">
                  <!-- <label for="search" class="visually-hidden">Search</label> -->
                  <input type="search" id="search" class="search-input" placeholder="Search" />
                  <img src="res\icons\x_icon.svg" alt="" class="search-icon" />
                </form>
              </div>
            </div>
            
              <div class="table-container">
                <div class="table-wrapper">
                    <table class="data-table attendance">
                        <thead>
                            <tr>
                                <th class="col-student-num">Student Number</th>
                                <th class="col-name">Surname</th>
                                <th class="col-name">First Name</th>
                                <th class="col-status">Status Today</th>
                                <th class="col-time">Time In</th>
                                <th class="col-count">On Time</th>
                                <th class="col-count">Lates</th>
                                <th class="col-count">Absences</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    // Format the time to be more readable
                                    $time_in = $row['time_in'] ? date('h:i A', strtotime($row['time_in'])) : '-';
                                    
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['student_num']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['surname']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['first_name']) . "</td>";
                                    echo "<td class='status-" . strtolower($row['status_today']) . "'>" . htmlspecialchars($row['status_today']) . "</td>";
                                    echo "<td class='time-cell'>" . $time_in . "</td>";
                                    echo "<td class='count-cell'>" . htmlspecialchars($row['on_time']) . "</td>";
                                    echo "<td class='count-cell'>" . htmlspecialchars($row['lates']) . "</td>";
                                    echo "<td class='count-cell'>" . htmlspecialchars($row['absences']) . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8' style='text-align: center;'>No records found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
          </div>
        </section>
      </div>
      <div class="bottom-outline"></div>
    </main>

    <script>
      // Function to update date and time every second
      function updateDateTime() {
        const dateDisplay = document.querySelector('.date-display');
        const timeDisplay = document.querySelector('.time-display');
        
        const currentDate = new Date();

        // Format date as "Nov 19, 2024"
        const formattedDate = currentDate.toLocaleDateString('en-US', {
          weekday: 'short', 
          month: 'short', 
          day: 'numeric', 
          year: 'numeric'
        });
        
        // Format time as "9:41 AM"
        const formattedTime = currentDate.toLocaleTimeString('en-US', {
          hour: 'numeric', 
          minute: 'numeric', 
          hour12: true
        });

        // Update the time and date on the page
        dateDisplay.textContent = formattedDate;
        timeDisplay.textContent = formattedTime;
      }

      // Call the function initially and then every second
      updateDateTime();
      setInterval(updateDateTime, 1000);
    </script>

    <script>
      $(document).ready(function() {
          let searchTimeout;
          const searchInput = $('.search-input');
          const tableType = $('table').hasClass('classlist') ? 'classlist' : 'attendance';
          
          // Function to update the table content
          function updateTable(data) {
              const tbody = $('.data-table tbody');
              tbody.empty();
              
              if (data.length === 0) {
                  const colSpan = tableType === 'classlist' ? '8' : '8';
                  tbody.append(`<tr><td colspan="${colSpan}" style="text-align: center;">No records found</td></tr>`);
                  return;
              }
              
              data.forEach(row => {
                  let tr = $('<tr>');
                  
                  if (tableType === 'classlist') {
                      tr.append(`
                          <td>${row.student_num}</td>
                          <td>${row.surname}</td>
                          <td>${row.first_name}</td>
                          <td>${row.birthday}</td>
                          <td>${row.email}</td>
                          <td>${row.contact_num}</td>
                          <td>${row.guardian_name}</td>
                          <td>${row.guardian_num}</td>
                      `);
                  } else {
                      tr.append(`
                          <td>${row.student_num}</td>
                          <td>${row.surname}</td>
                          <td>${row.first_name}</td>
                          <td class="status-${row.status_today.toLowerCase()}">${row.status_today}</td>
                          <td class="time-cell">${row.time_in || '-'}</td>
                          <td class="count-cell">${row.on_time}</td>
                          <td class="count-cell">${row.lates}</td>
                          <td class="count-cell">${row.absences}</td>
                      `);
                  }
                  
                  tbody.append(tr);
              });
          }
          
          // Function to perform search
          function performSearch(searchTerm, sort = '') {
              $.ajax({
                  url: 'search_handler.php',
                  method: 'GET',
                  data: {
                      table: tableType,
                      search: searchTerm,
                      sort: sort
                  },
                  success: function(response) {
                      updateTable(response);
                  },
                  error: function(xhr, status, error) {
                      console.error('Search error:', error);
                  }
              });
          }
          
          // Search input handler with debouncing
          searchInput.on('input', function() {
              const searchTerm = $(this).val().trim();
              
              clearTimeout(searchTimeout);
              searchTimeout = setTimeout(() => {
                  // Check if the input is a sorting keyword
                  const sortKeywords = ['surname', 'student number', 'time in', 'on time', 'late', 'absent'];
                  const isSort = sortKeywords.includes(searchTerm.toLowerCase());
                  
                  performSearch(isSort ? '' : searchTerm, isSort ? searchTerm : '');
              }, 300);
          });
          
          // Clear search when X icon is clicked
          $('.search-icon').click(function() {
              searchInput.val('');
              performSearch('');
          });
      });
    </script>
  </body>
</html>    