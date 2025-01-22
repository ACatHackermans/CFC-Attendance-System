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

    // Query to fetch data from the attendance log table
    $sql = "SELECT log_id, student_number, surname, name, log_date, time_in, status, guardian_num FROM attendance_log";
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
    <title>Student Attendance History - CFCSR Student Attendance Management System</title>
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
        height: 100vh;
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
        border: 2px solid #14AE5C;
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
      /* .datetime-wrapper {
        border-radius: 6px;
        display: flex;
        min-height: 42px;
        align-items: center;
        gap: 6px;
        justify-content: flex-end;
      }
      .date-display {
        border-radius: 6px;
        background-color: rgba(120, 120, 128, 0.12);
        padding: 8px 12px;
        font-family: "SF Pro", sans-serif;
        font-size: 16px;
        color: rgba(0, 153, 81, 1);
        text-align: center;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      } */
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
        min-width: 1200px; /* Ensures horizontal scroll on smaller screens */
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
      .col-student-num { width: 120px; }
      .col-name { width: 150px; }
      .col-birthday { width: 100px; }
      .col-email { width: 200px; }
      .col-contact { width: 120px; }
      .col-guardian { width: 150px; }
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

      #attendanceReportBtn {
            position: fixed; /* Makes it stay in one place regardless of scrolling */
            bottom: 20px; /* Distance from the bottom of the page */
            right: 20px; /* Distance from the right side of the page */
            padding: 12px;
            font-size: 16px;
            font-family: var(
              --single-line-body-base-font-family,
              "Inter-Regular",
              sans-serif
            );
            background-color: #009951;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            text-decoration: none;            
        }

        #attendanceReportBtn:hover {
          background-color: #12a054; /* Slightly darker blue on hover */
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
                  <div class="avatar" style="background-image: url('get_avatar.php?id=<?php echo $user_id; ?>'); background-size: cover; background-position: center;" role="img" aria-label="User avatar"></div>
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
          <header class="page-header">STUDENT ATTENDANCE HISTORY</header>
          
          <div class="content-wrapper">            
            <div class="controls-section">
              <div class="class-info">
                Grade: 12 
                <br />
                Section: Competitive
                <br />
                Strand: STEM
              </div>

              <!-- <div class="datetime-wrapper">
                <time class="date-display"></time>
              </div> -->

              <a id="attendanceReportBtn" href="attendancereport.php" class="button">
                <span>Attendance Report</span>
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
                  <table class="data-table classlist" role="grid">
                    <thead>
                      <tr>
                        <th class="col-id">Log ID</th>
                        <th class="col-student-num">Student Number</th>
                        <th class="col-name">Surname</th>
                        <th class="col-name">Name</th>
                        <th class="col-date">Log Date</th>
                        <th class="col-date">Time-in</th>
                        <th class="col-status">Status</th>
                        <th class="col-contact">Guardian Contact Number</th>
                      </tr>
                    </thead>
                  <tbody>
                    <?php
                      if ($result->num_rows > 0) {
                        // Output data for each row
                        while ($row = $result->fetch_assoc()) {
                          echo "<tr>";
                          echo "<td class='table-cell'>" . htmlspecialchars($row['log_id']) . "</td>";
                          echo "<td class='table-cell'>" . htmlspecialchars($row['student_number']) . "</td>";
                          echo "<td class='table-cell'>" . htmlspecialchars($row['surname']) . "</td>";
                          echo "<td class='table-cell'>" . htmlspecialchars($row['name']) . "</td>";
                          echo "<td class='table-cell'>" . htmlspecialchars($row['log_date']) . "</td>";
                          echo "<td class='table-cell'>" . htmlspecialchars($row['time_in']) . "</td>";
                          echo "<td class='table-cell'>" . htmlspecialchars($row['status']) . "</td>";
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

    <script>
      // Function to update dateevery second
      function updateDate() {
        const dateDisplay = document.querySelector('.date-display');
        
        const currentDate = new Date();

        // Format date as "Nov 19, 2024"
        const formattedDate = currentDate.toLocaleDateString('en-US', {
          weekday: 'short', 
          month: 'short', 
          day: 'numeric', 
          year: 'numeric'
        });

        // Update the date on the page
        dateDisplay.textContent = formattedDate;
      }

      // Call the function initially and then every second
      updateDate();
      setInterval(updateDateTime, 1000);
    </script>   
    
    <script>
    $(document).ready(function() {
        let searchTimeout;
        const searchInput = $('.search-input');
        const tableType = 'attendance_history';
        
        // Function to update the table content
        function updateTable(data) {
            const tbody = $('.data-table tbody');
            tbody.empty();
            
            if (data.length === 0) {
                tbody.append('<tr><td colspan="8" style="text-align: center;">No records found</td></tr>');
                return;
            }
            
            data.forEach(row => {
                let tr = $('<tr>');
                tr.append(`
                    <td>${row.log_id}</td>
                    <td>${row.student_number}</td>
                    <td>${row.surname}</td>
                    <td>${row.name}</td>
                    <td>${row.log_date}</td>
                    <td>${row.time_in}</td>
                    <td>${row.status}</td>
                    <td>${row.guardian_num}</td>
                `);
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
                // Check if the input is a sorting keyword or date format
                const sortKeywords = ['surname', 'student number', 'name', 'log date', 'time in', 'date asc', 'date desc', 'status'];
                const isSort = sortKeywords.includes(searchTerm.toLowerCase());
                const isDate = /^\d{4}-\d{2}-\d{2}$/.test(searchTerm);
                
                if (isDate) {
                    performSearch(searchTerm);
                } else {
                    performSearch(isSort ? '' : searchTerm, isSort ? searchTerm : '');
                }
            }, 300);
        });
        
        // Clear search when X icon is clicked
        $('.search-icon').click(function() {
            searchInput.val('');
            performSearch('');
        });
    });
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