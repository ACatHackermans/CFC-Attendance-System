<?php 
session_start();

  require("connection.php");

  if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $sql = "SELECT username FROM users WHERE user_id = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $user_id); // Assuming user_id is an integer
    $stmt->execute();
    $result = $stmt->get_result();

    $username = "";

    if ($result->num_rows > 0) {
        // Fetch the associated field information
        while ($row = $result->fetch_assoc()) {
            $username .= htmlspecialchars($row['username']);
        }
    } else {
      $username = "No record found.";
    }

    $stmt->close();
  } else {
    // Redirect to login if not logged in yet
    header("Location: ./login.php");
    die;
  }
?>

<!DOCTYPE html>

<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Home Dashboard - CFCSR Student Attendance Management System</title>
    <link rel="icon" type="image/x-icon" href="./res/img/favicon.ico">

    <style>
      html, body {
        margin: 0;
        padding: 0;
        width: 100%;
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
        /* min-width: 250px;
        max-width: 300px; */
        background-color: #f0f0f0;
      }
      .sidebar { 
        display: flex;
        flex-grow: 1;
        flex-direction: column;
        font: 400 16px/1.5 Roboto, sans-serif;
        padding: 15px;
        height: 100%;
      }
      .logo-image{
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
      .nav-item:hover {
        background-color:rgb(11, 158, 0);
        color: #fff;
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
      }
      .content-wrapper { width: 100%; }
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
      }
      .top-section {
        display: flex; /* Enable flexbox */
        align-items: flex-start; /* Align items to the top */
        justify-content: center; /* Space out the two sections */
        margin-top: 15px;
        width: 100%; /* Ensure the container takes up full width */
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
        padding: 11px 8px 11px;
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
      .calendar {        
        border: 1px solid #ccc;
        border-radius: 10px;
        padding: 20px;
        background: #fff;
        /* box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2); */
      }
      .calendar h2 {
        text-align: center;
        margin-bottom: 20px;
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

      .bottom-section {
        display: flex; /* Enable flexbox */
        justify-content: center; /* Distribute space between the items */
        align-items: center; /* Align items to the top */
        gap: 20px; /* Add some space between the form and NFC section */
        margin: 20px; /* Add padding around the container */
        background-color: #f1f1f1;
        border-radius: 6px;
        box-shadow: 0 10px 4px rgba(0, 0, 0, 0.25);
        padding: 25px;
        border: 1px solid #fff;
        color: #000000;
        text-align: center;
        font-family: "Rem-Regular", sans-serif;
      }
      .attendance-section {
        display: flex;
        align-items: center;
        flex-direction: column;
        width: 25%;
      }
      .attendance-list {
        display: flex;
        align-items: center;
        flex-direction: column;
        width: 100%;
        padding: 0;
        margin: 0;
      }
      .student-avatar {
        background-color: #d9d9d9;
        border-radius: 50%;
        width: 50px;
        height: 50px;
      }
      .attendance-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 5px;
        width: 100%;
        justify-content: center;
      }
      .status-columns {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        width: 75%;
      }    
      .status-column {
        height: 200px;
        font-size: 25px;
        font-weight: 600;
        margin-top: 20px;
        padding: 20px;
        border-radius: 10px;
        display: flex;
        justify-content: center;
      }    
      .status-late, .status-excused {
        background: linear-gradient(
        147.59deg,
        rgba(149, 196, 148, 1) 0%,
        rgba(252, 238, 28, 1) 100%
        );
        
      }    
      .status-absent {
        background-color: #e3e3e3;
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
        .top-section { margin-right: 10px; }
        .scroll-track {
          padding-bottom: 100px;
          margin-top: 40px;
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
                <div class="avatar" role="img" aria-label="User avatar"></div>
                <span class="username"><?php echo $username; ?></span>
              </div>
              <a class="nav-item" href="logout.php" style="font-weight: 700;"> <!-- target="_parent" -->
                <img src="res\icons\logout.svg" alt="" class="nav-icon" />
                Logout
              </a>
            </div>
          </nav>
        </aside>

        <section class="content-column">
          <div class="content-wrapper">
            <header class="page-header">DASHBOARD</header>
            
            <div class="top-section">
                <div class="welcome-and-events">
                  <div class="welcome">
                    Welcome, <?php echo $username; ?>.
                      <div class="user-avatar" role="img" aria-label="User avatar"></div>
                  </div>  
                      
                  <div class="events">
                    <h3 class="events-title">Events Today</h3>
                    <ul class="events-list">
                      <li class="event-item" tabindex="0">Event 1</li>
                      <li class="event-item" tabindex="0">Event 2</li>
                      <li class="event-item" tabindex="0">Event 3</li>
                      <li class="event-item" tabindex="0">Event 4</li>
                    </ul>
                  </div>
                </div>
                <div class="calendar">
                    <h2 id="month-year"></h2>
                    <div class="calendar-grid" id="calendar-grid">
                      <!-- Calendar will be dynamically generated -->
                    </div>
                </div>
            </div>    
            <div class="bottom-section">
              <div class="attendance-section">
                <h3>Attendance Today</h3>
                <ul class="attendance-list">
                  <li class="attendance-item">
                    <div class="student-avatar" role="img" aria-label="Student avatar"></div>
                    <span>Surname, Name</span>
                  </li>
                  <li class="attendance-item">
                    <div class="student-avatar" role="img" aria-label="Student avatar"></div>
                    <span>Surname, Name</span>
                  </li>
                  <li class="attendance-item">
                    <div class="student-avatar" role="img" aria-label="Student avatar"></div>
                    <span>Surname, Name</span>
                  </li>
                </ul>
              </div>

              <div class="status-columns">
                <div class="status-column status-late">
                  Late
                  
                </div>
                <div class="status-column status-absent">
                  Absentees

                </div>
                <div class="status-column status-excused">
                  Excused

                </div>
              </div>
            </div>            
          </div>
        </section>
      </div>
      <div class="bottom-outline"></div>
    </main>

    <script>
      // Initialize the calendar
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
      generateCalendar();
    </script>
  </body>
</html>    
