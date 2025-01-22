<?php
require("connection.php");

// Get the table type and search term from the request
$table = $_GET['table'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? '';

// Prepare base queries depending on table type
if ($table === 'classlist') {
    $baseQuery = "SELECT * FROM class_list WHERE 1=1";
    
    // Add search conditions if search term exists
    if (!empty($search)) {
        $search = "%$search%";
        $baseQuery .= " AND (student_num LIKE ? OR 
                           surname LIKE ? OR 
                           first_name LIKE ? OR 
                           guardian_name LIKE ?)";
    }
    
    // Add sorting
    if (!empty($sort)) {
        switch(strtolower($sort)) {
            case 'student number':
                $baseQuery .= " ORDER BY student_num ASC";
                break;
            case 'surname':
                $baseQuery .= " ORDER BY surname ASC";
                break;
            case 'first name':
                $baseQuery .= " ORDER BY student_num ASC";
                break;
            case 'birthday' || 'bday':
                $baseQuery .= " ORDER BY student_num ASC";
                break;
            default:
                $baseQuery .= " ORDER BY date_of_enrollment DESC";
        }
    }
    
    $stmt = $con->prepare($baseQuery);
    
    if (!empty($search)) {
        $stmt->bind_param("ssss", $search, $search, $search, $search);
    }
    
} else if ($table === 'attendance') {
    $baseQuery = "SELECT * FROM attendance_report WHERE 1=1";
    
    if (!empty($search)) {
        $search = "%$search%";
        $baseQuery .= " AND (student_num LIKE ? OR 
                           surname LIKE ? OR 
                           first_name LIKE ? OR 
                           status_today LIKE ?)";
    }
    
    // Add sorting
    if (!empty($sort)) {
        switch(strtolower($sort)) {
            case 'surname':
                $baseQuery .= " ORDER BY surname ASC";
                break;
            case 'first name':
                $baseQuery .= " ORDER BY student_num ASC";
                break;
            case 'time in':
                $baseQuery .= " ORDER BY time_in DESC";
                break;
            case 'on time':
                $baseQuery .= " ORDER BY status_today = 'on time' DESC, time_in ASC";
                break;
            case 'late':
                $baseQuery .= " ORDER BY status_today = 'late' DESC, time_in DESC";
                break;
            case 'absent':
                $baseQuery .= " ORDER BY status_today = 'absent' DESC";
                break;
            default:
                $baseQuery .= " ORDER BY time_in DESC";
        }
    }
    
    $stmt = $con->prepare($baseQuery);
    
    if (!empty($search)) {
        $stmt->bind_param("ssss", $search, $search, $search, $search);
    }
    
} else if ($table === 'attendance_history') {
    $baseQuery = "SELECT * FROM attendance_log WHERE 1=1";
    
    if (!empty($search)) {
        // Check if search is a date
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $search)) {
            $baseQuery .= " AND DATE(log_date) = ?";
            $searchType = 'date';
        } else {
            $search = "%$search%";
            $baseQuery .= " AND (student_number LIKE ? OR 
                               surname LIKE ? OR 
                               name LIKE ? OR 
                               status LIKE ? OR 
                               DATE_FORMAT(log_date, '%Y-%m-%d') LIKE ?)";
            $searchType = 'text';
        }
    }
    
    // Add sorting
    if (!empty($sort)) {
        switch(strtolower($sort)) {
            case 'student number':
                $baseQuery .= " ORDER BY student_number ASC";
                break;
            case 'surname':
                $baseQuery .= " ORDER BY surname ASC";
                break;
            case 'name':
                $baseQuery .= " ORDER BY name ASC";
                break;
            case 'log date':
                $baseQuery .= " ORDER BY log_date DESC";
                break;
            case 'time in':
                $baseQuery .= " ORDER BY time_in DESC";
                break;
            default:
                $baseQuery .= " ORDER BY log_date DESC, time_in DESC";
        }
    }
    
    $stmt = $con->prepare($baseQuery);
    
    if (!empty($search)) {
        $stmt->bind_param("ssss", $search, $search, $search, $search);
    }
}

// Execute query and get results
$stmt->execute();
$result = $stmt->get_result();
$data = array();

while ($row = $result->fetch_assoc()) {
    // Format time_in if it exists
    if (isset($row['time_in'])) {
        $row['time_in'] = date('h:i A', strtotime($row['time_in']));
    }
    $data[] = $row;
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($data);

$stmt->close();
$con->close();