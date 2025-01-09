<?php
header('Content-Type: application/json');
require("connection.php");

if ($_SERVER['REQUEST_METHOD'] == "GET") {
    // Read NFC card
    $output = shell_exec('python ./py/readcarduid.py');
    $nfc_data = json_decode($output, true);
    
    if (isset($nfc_data['uid'])) {
        // Check if card is already registered
        $stmt = $con->prepare("SELECT surname FROM class_list WHERE nfc_uid = ?");
        $stmt->bind_param("s", $nfc_data['uid']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $student = $result->fetch_assoc();
            echo json_encode([
                'uid' => $nfc_data['uid'],
                'isRegistered' => true,
                'surname' => $student['surname']
            ]);
        } else {
            echo json_encode([
                'uid' => $nfc_data['uid'],
                'isRegistered' => false
            ]);
        }
    } else {
        echo json_encode([
            'error' => $nfc_data['error'] ?? 'No card detected'
        ]);
    }
} else {
    echo json_encode([
        'error' => 'Invalid request method'
    ]);
}
?>