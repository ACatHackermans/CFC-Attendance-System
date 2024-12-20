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

    // Query to fetch data from the class_list table
    $sql = "SELECT student_num, surname, first_name, birthday, email, contact_num, guardian_name, guardian_num FROM class_list";
    $result = $con->query($sql);

    $stmt->close();
    $con->close();
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
    <title>Class List - CFCSR Student Attendance Management System</title>
    <link rel="icon" type="image/x-icon" href="./res/img/favicon.ico">

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
        background-color:#098100;
        color: #fff;
      }
      .nav-item:hover {
        background-color: rgb(11, 158, 0);
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
        display: flex;
        flex-wrap: wrap;
        margin: 10px;
      }
      .table-wrapper {
        display: flex;
        /* min-height: 682px; */
        flex-direction: column;
        flex-grow: 1;
        flex-basis: 0;
      }
      .data-table {
        border-radius: 4px;
        /* background-color: #363636; */
        background-color: #ffffff;
        border: 1px solid #5b5b5b;
        color: #000000;
        font: 600 12px/1.3 Inter, sans-serif;
      }
      .table-row {
        display: flex;
        width: 100%;
        background-color: rgba(255,255,255,0);
      }
      .table-cell {
        background-color: rgba(255,255,255,0.002);
        border: 1px solid #5b5b5b;
        flex: 1;
        padding: 10px 12px;
        /* min-height: 36px; */
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
              <a href="classlist.php" class="nav-item active">
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
              <a class="nav-item" href="logout.php" target="_parent" style="font-weight: 700;">
                <img src="res\icons\logout.svg" alt="" class="nav-icon" />
                Logout
              </a>
            </div>
          </nav>
        </aside>

        <section class="content-column">
          <header class="page-header">CLASS LIST</header>
          
          <div class="content-wrapper">            
            <div class="controls-section">
              <div class="class-info">
                Grade: 12 
                <br />
                Section: Competitive
                <br />
                Strand: STEM
              </div>
              
              <div class="search-controls">
                <div class="action-buttons">
                  <img src="res\icons\filter1.svg" alt="Add" class="nav-icon" />
                  <img src="res\icons\filter2.svg" alt="Edit" class="nav-icon" />
                </div>
                
                <form class="search-box" role="search">
                  <!-- <label for="search" class="visually-hidden">Search</label> -->
                  <input type="search" id="search" class="search-input" placeholder="Search" />
                  <img src="res\icons\x_icon.svg" alt="" class="search-icon" />
                </form>
              </div>
            </div>

            <div class="table-container">
              <div class="table-wrapper">
                <table class="data-table" role="grid">
                  <thead>
                    <tr class="table-row">
                      <th class="table-cell">Student Number</th>
                      <th class="table-cell">Surname</th>
                      <th class="table-cell">First Name</th>
                      <th class="table-cell">Birthday</th>
                      <th class="table-cell">Email</th>
                      <th class="table-cell">Contact Number</th>
                      <th class="table-cell">Guardian First Name</th>
                      <th class="table-cell">Guardian Contact Number</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                      if ($result->num_rows > 0) {
                        // Output data for each row
                        while ($row = $result->fetch_assoc()) {
                          echo "<tr class='table-row' style='text-align: center;'>";
                          echo "<td class='table-cell'>" . htmlspecialchars($row['student_num']) . "</td>";
                          echo "<td class='table-cell'>" . htmlspecialchars($row['surname']) . "</td>";
                          echo "<td class='table-cell'>" . htmlspecialchars($row['first_name']) . "</td>";
                          echo "<td class='table-cell'>" . htmlspecialchars($row['birthday']) . "</td>";
                          echo "<td class='table-cell'>" . htmlspecialchars($row['email']) . "</td>";
                          echo "<td class='table-cell'>" . htmlspecialchars($row['contact_num']) . "</td>";
                          echo "<td class='table-cell'>" . htmlspecialchars($row['guardian_name']) . "</td>";
                          echo "<td class='table-cell'>" . htmlspecialchars($row['guardian_num']) . "</td>";
                          echo "</tr>";
                        }
                      } else {
                        echo "<tr><td colspan='8' class='table-cell'>No records found</td></tr>";
                      }
                    ?>
                  </tbody>
                </table>
              </div>
              
              <!-- <div class="scroll-track">
                <div class="scroll-thumb"></div>
              </div> -->
            </div>
          </div>
        </section>
      </div>
      <div class="bottom-outline"></div>
    </main>
  </body>
</html>    