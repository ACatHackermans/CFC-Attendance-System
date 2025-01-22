<?php
require("connection.php");

if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    
    $stmt = $con->prepare("SELECT avatar, avatar_type FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if ($row['avatar'] && $row['avatar_type']) {
            header("Content-Type: " . $row['avatar_type']);
            echo $row['avatar'];
            exit;
        }
    }
}

// Return default avatar if no image found or error occurred
header("Content-Type: image/svg+xml");
echo '<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 50 50">
    <circle cx="25" cy="25" r="25" fill="#d9d9d9"/>
    <circle cx="25" cy="20" r="8" fill="#a0a0a0"/>
    <path d="M25,30 C16,30 12,35 12,40 L38,40 C38,35 34,30 25,30" fill="#a0a0a0"/>
</svg>';

// Close database connection
if (isset($stmt)) {
    $stmt->close();
}
if (isset($con)) {
    $con->close();
}