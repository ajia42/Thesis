<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit();
}

// Include database configuration
include("../../db_config.php");

// Validate service_id parameter
if (!isset($_GET['service_id']) || empty($_GET['service_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Service ID is required']);
    exit();
}

$service_id = mysqli_real_escape_string($conn, $_GET['service_id']);

// Query to get service details
$query = "SELECT service_name, service_fee FROM service_type WHERE service_type_id = '$service_id'";
$result = mysqli_query($conn, $query);

if (!$result) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database error: ' . mysqli_error($conn)]);
    exit();
}

$service = mysqli_fetch_assoc($result);

if (!$service) {
    header('HTTP/1.1 404 Not Found');
    echo json_encode(['error' => 'Service not found']);
    exit();
}

// Return the service details as JSON
header('Content-Type: application/json');
echo json_encode($service);
