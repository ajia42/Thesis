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

// Handle cancellation request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_appointment'])) {
    if (in_array(strtolower($appointment['status']), ['pending', 'scheduled'])) {
        $update_sql = "UPDATE appointment SET status = 'Cancelled' WHERE appointment_id = '$appointment_id'";
        mysqli_query($conn, $update_sql);
        // Refresh the appointment data
        $result = mysqli_query($conn, $sql);
        $appointment = mysqli_fetch_assoc($result);
    }
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
            margin-right: 10px;
        }

        .cancel-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 8px 15px;
            /* Match back-btn padding */
            background-color: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            /* Match back-btn font size */
            height: 34px;
            /* Match back-btn height */
            line-height: 1;
            /* Ensure text alignment matches */
            box-sizing: border-box;
            /* Consistent sizing */
        }

        .cancel-btn:hover {
            background-color: #c0392b;
        }

        .status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-pending {
            background-color: #e2f0fd;
            color: #0d6efd;
        }

        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-scheduled {

            background-color: #fff3cd;
            color: #856404;
        }

        .status-no-show {
            background-color: #f8d7da;
            color: #721c24;
            text-transform: capitalize;
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
            <div>
                <span class="status status-<?php echo strtolower($appointment['status']); ?>">
                    <?php echo htmlspecialchars($appointment['status']); ?>
                </span>
            </div>
        </div>

        <div class="detail-item">
            <div class="detail-label">Created At</div>
            <div><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($appointment['created_at']))); ?></div>
        </div>

        <?php if (!empty($appointment['symptoms'])): ?>
            <div class="detail-item">
                <div class="detail-label">Symptoms</div>
                <div><?php echo htmlspecialchars($appointment['symptoms']); ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($appointment['comment'])): ?>
            <div class="detail-item">
                <div class="detail-label">Admin Comment</div>
                <div><?php echo htmlspecialchars($appointment['comment']); ?></div>
            </div>
        <?php endif; ?>

        <!-- Add SweetAlert library -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <div class="action-buttons">
            <a href="patient_history.php" class="back-btn">Back to History</a>

            <?php if (in_array(strtolower($appointment['status']), ['pending', 'scheduled'])): ?>
                <form method="POST" id="cancelForm" style="display: inline;">
                    <input type="hidden" name="cancel_appointment" value="1">
                    <button type="button" class="cancel-btn" id="cancelBtn">
                        Cancel Appointment
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.getElementById('cancelBtn').addEventListener('click', function() {
            Swal.fire({
                title: 'Confirm Cancellation',
                text: "Are you sure you want to cancel this appointment?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#3498db',
                confirmButtonText: 'Yes, cancel it!',
                cancelButtonText: 'No, keep it'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading indicator
                    Swal.fire({
                        title: 'Processing',
                        html: 'Cancelling your appointment...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading()
                        }
                    });

                    // Submit the form
                    document.getElementById('cancelForm').submit();
                }
            });
        });
    </script>
</body>

</html>