<?php
include("../db_config.php");

function getAvailableTimeSlots($conn, $date, $current_time = null)
{
    $time_slots = [
        '08:00:00',
        '08:30:00',
        '09:00:00',
        '09:30:00',
        '10:00:00',
        '10:30:00',
        '13:00:00',
        '13:30:00',
        '14:00:00',
        '14:30:00',
        '15:00:00',
        '15:30:00',
        '17:00:00'
    ];

    // Get all booked time slots for the given date
    $booked_slots = [];
    $query = "SELECT booking_time FROM appointment WHERE booking_date = '$date'";
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $booked_slots[] = $row['booking_time'];
    }

    $available_slots = [];
    foreach ($time_slots as $slot) {
        // If it's today's date, check if time has passed
        if ($date == date('Y-m-d') && $current_time) {
            if (strtotime($slot) <= strtotime($current_time)) {
                continue; // Skip passed time slots
            }
        }

        // Check if slot is already booked
        if (!in_array($slot, $booked_slots)) {
            $available_slots[] = $slot;
        }
    }

    return $available_slots;
}

header('Content-Type: application/json');
$date = isset($_GET['date']) ? $_GET['date'] : '';
$current_time = isset($_GET['current_time']) ? $_GET['current_time'] : null;

if ($date) {
    echo json_encode(getAvailableTimeSlots($conn, $date, $current_time));
} else {
    echo json_encode([]);
}
