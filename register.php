<?php
  session_start();

  require("connection.php");

  if (isset($_SESSION['user_id'])) {
    header("Location: ./dashboard.php");
  }

  if($_SERVER['REQUEST_METHOD'] == "POST") {
    if($_POST['password'] == $_POST['confirm_password']) {
      $username = htmlspecialchars($_POST['username'], ENT_QUOTES);
      $password = password_hash(htmlspecialchars($_POST['password'], ENT_QUOTES), PASSWORD_BCRYPT);

      if ($username !== trim($username)) {
        echo "<script>alert('Username cannot start or end with a whitespace.');</script>";
      } else if (strpos($password, ' ') !== false) {
        echo "<script>alert('Password cannot have spaces.');</script>";
      } else {
        $username = trim($username);
        
        // Save to database
        $stmt = $con->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $password);

        if (!$stmt->execute()) {
          echo "<script>alert('Error: " . $stmt->error . "');</script>";
        } else {
          header("Location: login.php");
          die;
        }
      }
    }
  }
?>

<!DOCTYPE html>

<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register - CFCSR Student Attendance Management System</title>
    <link rel="icon" type="image/x-icon" href="./res/img/favicon.ico">

    <style>
      /* Global styles */
      html, body {
        margin: 0;
        padding: 0;
        width: 100%;
        min-height: 100vh;
      }

      /* Wrapper for the background with blur */
      .register-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        background: none;
        width: 100%;
        min-height: 100vh; /* Full viewport height */
        overflow: hidden; /* Prevent content overflow */
      }

      /* Pseudo-element for the blurred background */
      .register-wrapper::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: url("res/img/CFC Pic.jpg");
        background-size: cover;
        background-repeat: no-repeat;
        background-position: center;
        filter: blur(3px);
        z-index: 1;
      }

      .register-wrapper::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 2;
      }

      /* Content container */
      .auth-container {
        position: relative;
        z-index: 3;
        border-radius: 20px;
        background-color: #fff;
        display: flex;
        width: 400px;
        max-width: 90%;
        flex-direction: column;
        align-items: center;
        padding: 24px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      }

      /* Prevent layout issues with z-index stacking */
      .auth-container * {
        position: relative;
        z-index: 4;
      }

      .logo {
        object-fit: contain;
        object-position: center;
        width: 120px;
      }

      .system-title {
        color: #1C7600;
        letter-spacing: 1px;
        text-align: center;
        text-transform: uppercase;
        margin-top: 15px;
        font: 700 14px/1.5 Roboto, sans-serif;
      }

      .auth-header {
        color: #000;
        letter-spacing: 1.5px;
        text-align: center;
        margin-top: 30px;
        font: 600 32px/1.2 REM, sans-serif;
      }

      .auth-form {
        border-radius: 8px;
        background-color: #fff;
        align-self: stretch;
        display: flex;
        width: 100%;
        margin-top: 0px;
        flex-direction: column;
        padding: 20px;
        border: 1px solid #d9d9d9;
        box-sizing: border-box;
      }

      .form-group {
        display: flex;
        width: 100%;
        flex-direction: column;
        margin-bottom: 0px;
      }

      .form-label {
        color: #000;
        line-height: 1.4;
        margin-top: 8px;
        margin-bottom: 4px;
        font-size: 14px;
      }

      .form-input {
        border-radius: 8px;
        background-color: #fff;
        color: #666;
        line-height: 1.5;
        padding: 10px 14px;
        border: 1px solid #d9d9d9;
        font-size: 14px;
      }

      .submit-btn {
        border-radius: 8px;
        background-color: #009951;
        margin-top: 16px;
        width: 100%;
        padding: 12px;
        color: #fff;
        border: 1px solid #02542d;
        cursor: pointer;
        font-size: 16px;
        transition: background-color 0.3s;
      }

      .submit-btn:hover {
        background-color: #12a054;
      }

      .auth-link {
        text-decoration: none;
        margin-top: 15px;
        width: 100%;
        color: #000;
        line-height: 1.4;
        font-size: 14px;
        text-align: center;
        cursor: pointer;
      }
      .auth-link:hover {
        text-decoration: underline;
      }

      .visually-hidden {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        border: 0;
      }

      @media (max-width: 1000px) {
        .auth-container {
          width: 90%;
          padding: 16px;
        }
        .auth-header {
          margin-top: 20px;
          font-size: 28px;
        }
        .auth-form {
          padding: 16px;
        }
      }
    </style>
  </head>

  <body>
    <section class="register-wrapper">
        <main class="auth-container">
          <img
            loading="lazy"
            src="res\img\CFC Logo.svg"
            class="logo"
            alt="Student Attendance Management System Logo"
          />
          <h1 class="system-title">
            Student Attendance Management System
          </h1>
          <h2 class="auth-header">REGISTER</h2>
          <form class="auth-form" action="<?php htmlspecialchars($_SERVER["PHP_SELF"])?>" method="post">
            <div class="form-group">
              <label for="username" class="form-label">Username</label>
              <input 
                type="text" 
                id="username" 
                name="username"
                class="form-input" 
                placeholder="Enter your username"
                required
              />
            </div>
            <div class="form-group">
              <label for="password" class="form-label">Password</label>
              <input 
                type="password" 
                id="password"
                name="password" 
                class="form-input" 
                placeholder="Enter your password"
                oninput="check()"
                required
              />
            </div>
            <div class="form-group">
              <label for="password" class="form-label">Confirm Password</label>
              <input 
                type="password" 
                id="confirm_password"
                name="confirm_password" 
                class="form-input" 
                placeholder="Re-enter your password"
                oninput="check()"
                required
              />
            </div>
            <p id="message"></p>
            <input type="submit" class="submit-btn" name="submit" id="submit" value="Sign Up">
            <a href="login.php" class="auth-link">Already have an account?</a>
          </form>
        </main>
      </section>

      <script src="script.js"></script>
    </body>    
</html>