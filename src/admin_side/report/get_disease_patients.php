<?php
// get_disease_patients.php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit();
}

// Include database configuration
include("../../db_config.php");

// Validate disease_id parameter
if (!isset($_GET['disease_id']) || empty($_GET['disease_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Disease ID is required']);
    exit();
}

$disease_id = mysqli_real_escape_string($conn, $_GET['disease_id']);
$start_date = isset($_GET['start_date']) ? mysqli_real_escape_string($conn, $_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? mysqli_real_escape_string($conn, $_GET['end_date']) : '';

// Build date condition
$date_condition = "";
if (!empty($start_date) && !empty($end_date)) {
    $date_condition = "AND t.date BETWEEN '$start_date' AND '$end_date'";
} elseif (!empty($start_date)) {
    $date_condition = "AND t.date >= '$start_date'";
} elseif (!empty($end_date)) {
    $date_condition = "AND t.date <= '$end_date'";
}

// Query to get patients with this disease
$query = "SELECT p.patient_id, p.first_name, p.last_name, t.date AS treatment_date
          FROM patient p
          JOIN treatment t ON p.patient_id = t.patient_id
          JOIN treatment_disease td ON t.treatment_id = td.treatment_id
          WHERE td.disease_id = '$disease_id' $date_condition
          ORDER BY t.date DESC";
$result = mysqli_query($conn, $query);

if (!$result) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database error: ' . mysqli_error($conn)]);
    exit();
}

$patients = [];
while ($row = mysqli_fetch_assoc($result)) {
    $patients[] = $row;
}

// Return the patients as JSON
header('Content-Type: application/json');
echo json_encode($patients);
