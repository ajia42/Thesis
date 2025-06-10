<?php
session_start();
include("../db_config.php");

if (!isset($_SESSION['patient_id'])) {
    header("HTTP/1.1 403 Forbidden");
    exit();
}

if (!isset($_GET['date'])) {
    header("HTTP/1.1 400 Bad Request");
    exit();
}

$date = mysqli_real_escape_string($conn, $_GET['date']);

$sql = "SELECT booking_time FROM appointment 
        WHERE booking_date = '$date' 
        AND status != 'Cancelled'";
$result = mysqli_query($conn, $sql);

$bookedSlots = [];
while ($row = mysqli_fetch_assoc($result)) {
    $bookedSlots[] = $row['booking_time'];
}

header('Content-Type: application/json');
echo json_encode($bookedSlots);

$conn->close();
