<?php
include("../db_config.php");

$date = $_GET['date'] ?? '';

$bookedTimes = [];
if ($date) {
    $query = "SELECT booking_time FROM appointment WHERE booking_date = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $bookedTimes[] = $row['booking_time'];
    }
}

header('Content-Type: application/json');
echo json_encode($bookedTimes);
