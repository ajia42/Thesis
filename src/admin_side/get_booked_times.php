<?php
include("../db_config.php");

if (isset($_GET['date'])) {
    $date = mysqli_real_escape_string($conn, $_GET['date']);

    $query = "SELECT booking_time, status FROM appointment 
              WHERE booking_date = '$date'";
    $result = mysqli_query($conn, $query);

    $bookedTimes = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $bookedTimes[] = array(
            'time' => $row['booking_time'],
            'status' => $row['status']
        );
    }

    header('Content-Type: application/json');
    echo json_encode($bookedTimes);
}
