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
    <title>Attendance Login - CFCSR Student Attendance Management System</title>
    <link rel="icon" type="image/x-icon" href="./res/img/favicon.ico">

    <style>
      html, body {
        margin: 0;
        padding: 0;
        width: 100%;
        min-height: 100vh;
      }
      .page-wrapper {
        box-sizing: border-box;
        width: 100%;
        min-height: 100vh; /* Full viewport height */
        overflow-y: auto; 
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
      .nav-item:hover {
        text-decoration: underline;
      }
      /* .nav-item.active {
          background-color: #098100;
          color: #fff;
      } */
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
      .logout-btn {
          border-radius: 10px;
          display: flex;
          align-items: center;
          gap: 12px;
          padding: 12px 15px;
          color: #343434;
          text-decoration: none;
      }
      .logout-btn:hover {
        text-decoration: underline;
      }
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
      .subtitle {
        color: #000000;
        font-family: "Rem-Medium", sans-serif;
        font-size: 30px;
        line-height: 100%;
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
      .attendance-login {
        display: flex; /* Enable flexbox */
        justify-content: space-between; /* Distribute space between the items */
        align-items: flex-start; /* Align items to the top */
        gap: 20px; /* Add some space between the form and NFC section */
        margin: 20px; /* Add padding around the container */
      }      
      .loggingin-container {
        background: linear-gradient(
          180deg,
          rgba(255, 255, 255, 0.15) 0%,
          rgba(2, 27, 0, 0.15) 100%
        );
        border-radius: 10px;
        border-width: 3px;
        border-style: solid;
        border-image: linear-gradient(
          180deg,
          rgba(9, 129, 0, 1) 0%,
          rgba(2, 27, 0, 1) 100%
        );
        border-image-slice: 1;
        box-shadow: 0 10px 4px rgba(0, 0, 0, 0.25);
        padding: 37px 19px 84px;
        width: 75%;
        text-align: left;
        font-family: "Rem-Regular", sans-serif;
        font-size: 20px;
        font-weight: 400;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        gap: 30px;
      }
      .student-avatar {
          background-color: #d9d9d9;
          border-radius: 50%;
          width: 150px;
          height: 150px;
      }      
      .nfc-section {
        background: linear-gradient(
          180deg,
          rgba(255, 255, 255, 0.15) 0%,
          rgba(2, 27, 0, 0.15) 100%
        );
        border-radius: 10px;
        border-width: 3px;
        border-style: solid;
        border-image: linear-gradient(
          180deg,
          rgba(9, 129, 0, 1) 0%,
          rgba(2, 27, 0, 1) 100%
        );
        border-image-slice: 1;
        box-shadow: 0 10px 4px rgba(0, 0, 0, 0.25);
        padding: 10px;
        font: 400 30px REM, sans-serif;
        width: 25%;
        align-items: center;
        display: flex;
        flex-direction: column;
        gap: 20px;
      }
      .NFC-icon {
        /* aspect-ratio: 0.99; */
        object-fit: contain;
        width: 200px;
        align-self: center;
      }      
      .button {
        background-color: #009951;
        border-radius: 8px;
        /* border-style: solid;
        border-color: transparent;
        border-width: 1px; */
        padding: 12px;
        /* display: flex;
        flex-direction: row; */
        /* gap: var(--var-sds-size-space-200, 8px); */
        align-items: center;
        /* justify-content: center; */
        /* width: 50px; */
        /* margin-left: 20px; */
        text-decoration: none;
        color: #f5f5f5;
        /* text-align: left; */
        font-family: var(
          --single-line-body-base-font-family,
          "Inter-Regular",
          sans-serif
        );
        /* font-size: var(--single-line-body-base-font-size, 16px); */
        /* line-height: var(--single-line-body-base-line-height, 100%); */
        /* font-weight: var(--single-line-body-base-font-weight, 400); */
        /* position: relative; */
      }
      .button:hover {
        background-color: #12a054;
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

        .form-container,
        .nfc-section {
          width: 100%;
          margin: 13px 0 0;
        }
      
        .nfc-section {
          padding: 100px 20px 0 0;
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
              <a class="logout-btn" href="logout.php" target="_parent" style="font-weight: 700;">
                <img src="res\icons\logout.svg" alt="" class="nav-icon" />
                Logout
              </a>
            </div>
          </nav>
        </aside>

        <section class="content-column">
          <div class="content-wrapper">
            <header class="page-header">STUDENT ATTENDANCE LOGIN & NFC ENROLLMENT</header>
            
            <div class="controls-section">
              <div class="class-info">
                Grade: 12 
                <br />
                Section: Competitive
                <br />
                Strand: STEM
              </div>

              <div class="subtitle">LOGIN</div>

              <div class="datetime-wrapper">
                <time class="date-display">Nov 19, 2024</time>
                <time class="time-display">9:41 AM</time>
              </div>

              <a href="enrollcard.php" class="button">
                <span>Enroll New NFC Card</span>
              </a>
            </div>    
            <div class="attendance-login">
              <div class="loggingin-container">
                <div class="student-avatar" role="img" aria-label="Student Profile"></div>
                  Surname, Name
                  <br /><br />
                  Time In: --:--
                  <br />
                  Time Out: --:--
                  <br />
                  Status: ---
                  <br />
                  <!--Guardian Notification Sent ✓-->
              </div>

              <aside class="nfc-section">
                <img src="res\icons\NFC.svg" alt="NFC icon" class="NFC-icon" />
                Please tap NFC-enabled card on the NFC reader to take attendance...
              </aside>
            </div>            
          </div>
        </section>
      </div>
    </main>

    <script>
      
    </script>
  </body>
</html>    