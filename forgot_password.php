<?php
session_start();
require("connection.php");

if (isset($_SESSION['user_id'])) {
    header("Location: ./dashboard.php");
    die;
}

if($_SERVER['REQUEST_METHOD'] == "POST") {
    $username = htmlspecialchars($_POST['username'], ENT_QUOTES);
    $current_password = $_POST['current_password'];
    $new_password = htmlspecialchars($_POST['new_password'], ENT_QUOTES);
    $confirm_password = htmlspecialchars($_POST['confirm_password'], ENT_QUOTES);

    // First verify the current username and password
    $stmt = $con->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();

    if ($result->num_rows > 0 && password_verify($current_password, $user_data['password'])) {
        // Current credentials are correct, now check if new passwords match
        if($new_password === $confirm_password) {
            // Update with new password
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $update_stmt = $con->prepare("UPDATE users SET password = ? WHERE username = ?");
            $update_stmt->bind_param("ss", $hashed_password, $username);
            
            if($update_stmt->execute()) {
                echo "<script>alert('Password updated successfully. Please login with your new password.');</script>";
                echo "<script>window.location.href = 'login.php';</script>";
                die;
            } else {
                echo "<script>alert('Error updating password.');</script>";
            }
        } else {
            echo "<script>alert('New passwords do not match.');</script>";
        }
    } else {
        echo "<script>alert('Invalid username or current password.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Forgot Password - CFCSR Student Attendance Management System</title>
    <link rel="icon" type="image/x-icon" href="./res/img/favicon.ico">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            min-height: 100vh;
        }

        .forgot-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            background: none;
            width: 100%;
            min-height: 100vh;
            overflow: hidden;
        }

        .forgot-wrapper::before {
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

        .forgot-wrapper::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 2;
        }

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
            margin-top: 40px;
            font: 600 32px/1.2 REM, sans-serif;
        }

        .auth-form {
            border-radius: 8px;
            background-color: #fff;
            align-self: stretch;
            display: flex;
            width: 100%;
            margin-top: 20px;
            flex-direction: column;
            padding: 20px;
            border: 1px solid #d9d9d9;
            box-sizing: border-box;
        }

        .form-group {
            display: flex;
            width: 100%;
            flex-direction: column;
            margin-bottom: 16px;
        }

        .form-label {
            color: #000;
            line-height: 1.4;
            margin-bottom: 8px;
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
            width: 100%;
            padding: 12px;
            color: #fff;
            border: 1px solid #02542d;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
            margin-top: 8px;
        }

        .submit-btn:hover {
            background-color: #12a054;
        }

        .auth-link {
            text-decoration: none;
            margin-top: 16px;
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
    <section class="forgot-wrapper">
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
            <h2 class="auth-header">RESET PASSWORD</h2>
            <form class="auth-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"])?>" method="post">
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
                    <label for="current_password" class="form-label">Current Password</label>
                    <input 
                        type="password" 
                        id="current_password"
                        name="current_password" 
                        class="form-input" 
                        placeholder="Enter your current password"
                        required
                    />
                </div>
                <div class="form-group">
                    <label for="new_password" class="form-label">New Password</label>
                    <input 
                        type="password" 
                        id="new_password"
                        name="new_password" 
                        class="form-input" 
                        placeholder="Enter your new password"
                        required
                    />
                </div>
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <input 
                        type="password" 
                        id="confirm_password"
                        name="confirm_password" 
                        class="form-input" 
                        placeholder="Re-enter your new password"
                        required
                    />
                </div>
                <input type="submit" class="submit-btn" value="Reset Password">
                <a href="login.php" class="auth-link">Back to Login</a>
            </form>
        </main>
    </section>
</body>
</html>