<?php
session_start();
include("../db_config.php");

// Check if patient is logged in
if (!isset($_SESSION['patient_id'])) {
    header("Location: patient_login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: patient_history.php");
    exit();
}

$appointment_id = $_GET['id'];
$patient_id = $_SESSION['patient_id'];

// Fetch appointment details
$sql = "SELECT a.*, s.service_name 
        FROM appointment a
        JOIN service_type s ON a.service_type_id = s.service_type_id
        WHERE a.appointment_id = '$appointment_id' AND a.patient_id = '$patient_id'";
$result = mysqli_query($conn, $sql);
$appointment = mysqli_fetch_assoc($result);

if (!$appointment) {
    header("Location: patient_history.php");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Details - Vision Care</title>
    <style>
        /* Add similar styles as in patient_history.php */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
        }

        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .detail-item {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 8px 15px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Appointment Details</h1>

        <div class="detail-item">
            <div class="detail-label">Appointment ID</div>
            <div><?php echo htmlspecialchars($appointment['appointment_id']); ?></div>
        </div>

        <div class="detail-item">
            <div class="detail-label">Service</div>
            <div><?php echo htmlspecialchars($appointment['service_name']); ?></div>
        </div>

        <div class="detail-item">
            <div class="detail-label">Date</div>
            <div><?php echo htmlspecialchars(date('d/m/Y', strtotime($appointment['booking_date']))); ?></div>
        </div>

        <div class="detail-item">
            <div class="detail-label">Time</div>
            <div><?php echo htmlspecialchars(date('H:i', strtotime($appointment['booking_time']))); ?></div>
        </div>

        <div class="detail-item">
            <div class="detail-label">Status</div>
            <div><?php echo htmlspecialchars($appointment['status']); ?></div>
        </div>

        <?php if (!empty($appointment['symptoms'])): ?>
            <div class="detail-item">
                <div class="detail-label">Symptoms</div>
                <div><?php echo htmlspecialchars($appointment['symptoms']); ?></div>
            </div>
        <?php endif; ?>

        <a href="patient_history.php" class="back-btn">Back to History</a>
    </div>
</body>

</html>