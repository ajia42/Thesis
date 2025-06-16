
<?php
// get_booked_times.php
session_start();

// Set Laos timezone
date_default_timezone_set('Asia/Vientiane');

// Security check
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    exit();
}

// Include database configuration
include("../db_config.php");

// Set content type to JSON
header('Content-Type: application/json');

// Get the date parameter
$date = isset($_GET['date']) ? mysqli_real_escape_string($conn, $_GET['date']) : '';

if (empty($date)) {
    echo json_encode([]);
    exit();
}

// Query to get booked time slots for the specified date
$query = "SELECT booking_time FROM appointment 
          WHERE booking_date = '$date' 
          AND status NOT IN ('cancelled', 'no_show')
          ORDER BY booking_time";

$result = mysqli_query($conn, $query);

$bookedTimes = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $bookedTimes[] = $row['booking_time'];
    }
}

// Return JSON response
echo json_encode($bookedTimes);
?>