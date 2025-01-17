<?php
require_once 'vendor/autoload.php';

use Twilio\Rest\Client;

function sendSMS($to, $message) {

    $SMS_ENABLED = false;
    
    if (!$SMS_ENABLED) {
        return;
    }

    $accountSid = 'ACab9d41378d20a1d479c75176bfe07689';
    $authToken = '11836789c896fb2c05f40d50bb518ae7';
    $twilioNumber = '+15077135788';

    $client = new Client($accountSid, $authToken);

    try {
        $client->messages->create(
            $to,
            [
                'from' => $twilioNumber,
                'body' => $message,
            ]
        );
        echo "Message sent successfully to $to.";
    } catch (Exception $e) {
        echo "Failed to send message: " . $e->getMessage();
    }
}
?>
