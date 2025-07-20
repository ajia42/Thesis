<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit();
}

// Include database configuration
include("../db_config.php");

// Set headers for JSON response
header('Content-Type: application/json');

// Validate receipt_id parameter
if (!isset($_GET['receipt_id']) || empty($_GET['receipt_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Receipt ID is required']);
    exit();
}

$receipt_id = mysqli_real_escape_string($conn, $_GET['receipt_id']);

// Query to get services associated with the receipt
$query = "SELECT service_type_id 
          FROM receipt_service_type 
          WHERE receipt_id = '$receipt_id'";
$result = mysqli_query($conn, $query);

if (!$result) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database error: ' . mysqli_error($conn)]);
    exit();
}

$services = [];
while ($row = mysqli_fetch_assoc($result)) {
    $services[] = $row;
}

// Return the services as JSON
echo json_encode($services);
