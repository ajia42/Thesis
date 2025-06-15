<?php
session_start();
include("../db_config.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Replace the current checks with:
if (!isset($_SESSION['user_name'])) {
    if (isset($_SESSION['registered_phone'])) {
        header("Location: user_info.php");
    } else {
        header("Location: user_login.php");
    }
    exit();
}

$patient_id = $_SESSION['registered_phone'];
$patient_name = $_SESSION['user_name'];

// Fetch all appointments for the patient, newest first
$appointments = [];

$sql = "SELECT appointment_id, booking_date, booking_time, status 
        FROM appointment 
        WHERE patient_id = '$patient_id'
        ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $appointments[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment History - Vision Care</title>
    <link rel="stylesheet" href="user_login.css">
    <style>
        /* Base styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
        }

        .container {
            width: 100%;
            padding: 0 15px;
            box-sizing: border-box;
        }

        header {
            background-color: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .icon {
            width: 24px;
            height: 24px;
            margin-right: 10px;
        }

        .auth-links {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .auth-links a {
            color: #3498db;
            text-decoration: none;
        }

        /* Main content styles */
        .history-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 0 15px;
            box-sizing: border-box;
        }

        .welcome-name {
            text-align: center;
            margin: 0 auto 10px;
            font-size: 1rem;
            color: #555;
        }

        .welcome-message {
            margin-bottom: 20px;
        }

        .welcome-message h1 {
            font-size: 1.5rem;
            margin-bottom: 5px;
            margin-top: 20px;
        }

        .new-appointment-btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.2s;
            font-weight: 500;
            margin-bottom: 20px;
            display: inline-block;
            text-decoration: none;
        }

        .new-appointment-btn:hover {
            background-color: #2980b9;
        }

        .history-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 30px;
        }

        .history-section h2 {
            font-size: 1.3rem;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .appointments-list {
            max-height: 500px;
            overflow-y: auto;
        }

        .appointment-card {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .appointment-card:first-child {
            border: 2px solid #3498db;
            background-color: #f8f9fa;
        }

        .appointment-info {
            flex: 1;
        }

        .appointment-info h3 {
            margin: 0 0 5px 0;
            font-size: 1.1rem;
        }

        .appointment-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 5px;
        }

        .appointment-meta div {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
            color: #666;
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

        .detail-btn {
            background-color: #f8f9fa;
            color: #212529;
            border: 1px solid #ddd;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s;
            text-decoration: none;
        }

        .detail-btn:hover {
            background-color: #e2e6ea;
            border-color: #dae0e5;
        }

        /* Responsive adjustments */
        @media (max-width: 600px) {
            .appointment-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .appointment-meta {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="container header-content">
            <div class="logo">
                <svg viewBox="0 0 24 24" fill="currentColor" class="icon">
                    <path d="M12 4a4 4 0 100 8 4 4 0 000-8zM2 12C2 6.486 6.486 2 12 2s10 4.486 10 10-4.486 10-10 10S2 17.514 2 12z"></path>
                </svg>
                <span>Vision Care</span>
            </div>
            <div class="auth-links">
                <a href="user_profile.php">profile</a>
                <a href="user_logout.php">Logout</a>
            </div>
        </div>
    </header>

    <main class="container history-container">
        <div class="welcome-message">
            <div class="welcome-name">Welcome, <?php echo htmlspecialchars($patient_name); ?></div>
            <h1>Appointment History</h1>
            <p>View and manage your past and upcoming appointments.</p>
        </div>

        <a href="user_appointment.php" class="new-appointment-btn">New Appointment</a>

        <div class="history-section">
            <h2>History</h2>
            <div class="appointments-list">
                <?php if (empty($appointments)): ?>
                    <p>No appointments found.</p>
                <?php else: ?>
                    <?php foreach ($appointments as $index => $appointment): ?>
                        <div class="appointment-card <?php echo $index === 0 ? 'latest-appointment' : ''; ?>">
                            <div class="appointment-info">
                                <h3>Appointment #<?php echo htmlspecialchars($appointment['appointment_id']); ?></h3>
                                <div class="appointment-meta">
                                    <div>
                                        <svg viewBox="0 0 24 24" width="14" height="14" fill="#666">
                                            <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"></path>
                                        </svg>
                                        <span><?php echo htmlspecialchars(date('d/m/Y', strtotime($appointment['booking_date']))); ?></span>
                                    </div>
                                    <div>
                                        <svg viewBox="0 0 24 24" width="14" height="14" fill="#666">
                                            <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"></path>
                                            <path d="M12.5 7H11v6l5.25 3.15.75-1.23-4.5-2.67z"></path>
                                        </svg>
                                        <span><?php echo htmlspecialchars(date('H:i', strtotime($appointment['booking_time']))); ?></span>
                                    </div>
                                    <div>
                                        <span class="status status-<?php echo strtolower($appointment['status']); ?>">
                                            <?php echo htmlspecialchars($appointment['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <a href="user_appointment_detail.php?id=<?php echo htmlspecialchars($appointment['appointment_id']); ?>" class="detail-btn">Details</a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>

</html>