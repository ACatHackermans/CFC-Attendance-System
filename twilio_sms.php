<?php
require_once 'vendor/autoload.php';
use Twilio\Rest\Client;

function sendSMS($to, $message) {
    // Set this to false to disable SMS sending
    $SMS_ENABLED = true;
    
    if (!$SMS_ENABLED) {
        return ['success' => false, 'message' => 'SMS sending disabled'];
    }

    $accountSid = 'ACab9d41378d20a1d479c75176bfe07689';
    $authToken = '991b3ef50da1280e97d5522ae4741b48';
    $twilioNumber = '+15077135788';

    $client = new Client($accountSid, $authToken);

    try {
        $client->messages->create(
            $to, // Recipient's phone number
            [
                'from' => $twilioNumber,
                'body' => $message,
            ]
        );
        return ['success' => true, 'message' => "Message sent successfully to $to"];
    } catch (Exception $e) {
        return ['success' => false, 'message' => "Failed to send message: " . $e->getMessage()];
    }
}
?>