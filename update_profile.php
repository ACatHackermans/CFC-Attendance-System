<?php
session_start();
require("connection.php");
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$response = ['success' => false];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $user_id = $_SESSION['user_id'];
        
        // Handle username update
        if (isset($_POST['username'])) {
            $new_username = trim(htmlspecialchars($_POST['username'], ENT_QUOTES));
            
            // Validate username
            if (!preg_match("/^[a-zA-Z0-9\s._]{3,20}$/", $new_username)) {
                throw new Exception("Username must be 3-20 characters and contain only letters, numbers, spaces, underscores, or periods.");
            }
            
            // Check if username exists
            $stmt = $con->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
            $stmt->bind_param("si", $new_username, $user_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("Username already taken.");
            }
            
            // Update username
            $stmt = $con->prepare("UPDATE users SET username = ? WHERE user_id = ?");
            $stmt->bind_param("si", $new_username, $user_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update username.");
            }
            
            $response['success'] = true;
            $response['message'] = "Username updated successfully.";
            $response['username'] = $new_username;
        }
        
        // Handle avatar upload
        if (isset($_FILES['avatar'])) {
            $file = $_FILES['avatar'];
            
            // Validate file
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($file['type'], $allowed_types)) {
                throw new Exception("Invalid file type. Only JPG, PNG and GIF allowed.");
            }
            
            if ($file['size'] > 5242880) { // 5MB limit
                throw new Exception("File too large. Maximum size is 5MB.");
            }
            
            // Read and prepare file data
            $image_data = file_get_contents($file['tmp_name']);
            $image_type = $file['type'];
            
            // Update avatar in database
            $stmt = $con->prepare("UPDATE users SET avatar = ?, avatar_type = ? WHERE user_id = ?");
            $stmt->bind_param("ssi", $image_data, $image_type, $user_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update avatar.");
            }
            
            $response['success'] = true;
            $response['message'] = "Avatar updated successfully.";
            $response['avatar_url'] = "get_avatar.php?id=" . $user_id . "&t=" . time();
        }
    }
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>