<?php
require("connection.php");

// Lock file to prevent multiple instances running
$lock_file = __DIR__ . '/notification_queue.lock';
if (file_exists($lock_file)) {
    $lock_time = filemtime($lock_file);
    // If lock file is older than 5 minutes, consider it stale
    if (time() - $lock_time < 300) {
        die("Another instance is already running\n");
    }
}
file_put_contents($lock_file, time());

try {
    // Get pending notifications
    $query = "
        SELECT * FROM notification_queue 
        WHERE status = 'pending' 
        AND attempts < 3 
        ORDER BY created_at ASC 
        LIMIT 5"; // Process 5 at a time
    
    $result = $con->query($query);
    
    while ($notification = $result->fetch_assoc()) {
        // Mark as processing
        $con->query("
            UPDATE notification_queue 
            SET status = 'processing', 
                last_attempt = CURRENT_TIMESTAMP 
            WHERE queue_id = {$notification['queue_id']}
        ");
        
        // Prepare command
        $escaped_message = escapeshellarg($notification['message']);
        $escaped_phone = escapeshellarg($notification['guardian_phone']);
        $command = "python ./py/smsnotif.py {$escaped_phone} {$escaped_message}";
        
        // Execute SMS sending script
        $output = shell_exec($command);
        $sms_result = json_decode($output, true);
        
        // Update status based on result
        $new_status = ($sms_result && $sms_result['success']) ? 'completed' : 'pending';
        $attempts = $notification['attempts'] + 1;
        
        if ($attempts >= 3 && $new_status === 'pending') {
            $new_status = 'failed';
        }
        
        // Update notification record
        $con->query("
            UPDATE notification_queue 
            SET status = '{$new_status}', 
                attempts = {$attempts}, 
                last_attempt = CURRENT_TIMESTAMP 
            WHERE queue_id = {$notification['queue_id']}
        ");
        
        // Log the attempt
        error_log(sprintf(
            "SMS Queue Processing - ID: %d, Phone: %s, Status: %s, Attempts: %d",
            $notification['queue_id'],
            $notification['guardian_phone'],
            $new_status,
            $attempts
        ));
        
        // Sleep between sends to avoid overwhelming the GSM module
        sleep(2);
    }
    
} catch (Exception $e) {
    error_log("Error processing notification queue: " . $e->getMessage());
} finally {
    // Clean up lock file
    unlink($lock_file);
    if (isset($con)) $con->close();
}
?>