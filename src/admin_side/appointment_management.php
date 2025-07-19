<?php
session_start();

// Set Laos timezone
date_default_timezone_set('Asia/Vientiane');

$current_date = date('Y-m-d');
$current_time = date('H:i:s');

if (!isset($_SESSION['admin_id'])) {
    header('Location: signin_admin.php');
    exit();
}

// // Security headers to prevent caching
// header("Cache-Control: no-cache, no-store, must-revalidate");
// header("Pragma: no-cache");
// header("Expires: 0");

// Include database configuration
include("../db_config.php");

// Function to generate next appointment ID
function generateAppointmentID($conn)
{
    $sql = "SELECT MAX(appointment_id) AS max_id FROM appointment";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    if (empty($row['max_id'])) {
        return 'A0001';
    }

    $lastID = $row['max_id'];
    $numPart = intval(substr($lastID, 1));
    $newNumPart = $numPart + 1;

    return 'A' . str_pad($newNumPart, 4, '0', STR_PAD_LEFT);
}

// Define available time slots in 24-hour format
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
];

// Validation checks
$errors = '';
$message = '';

// Form processing code (same as original - not modified)
// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Common sanitization for appointment_id if present
    $appointment_id = isset($_POST['appointment_id']) ? mysqli_real_escape_string($conn, $_POST['appointment_id']) : '';

    // Action based on button click
    if (isset($_POST['save_button'])) {
        // Sanitize and validate input fields for save operation
        $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
        $service_type_id = mysqli_real_escape_string($conn, $_POST['service_type_id']);
        $booking_time = mysqli_real_escape_string($conn, $_POST['booking_time']);
        $booking_date = mysqli_real_escape_string($conn, $_POST['booking_date']);
        $symptoms = isset($_POST['symptoms']) ? mysqli_real_escape_string($conn, substr($_POST['symptoms'], 0, 30)) : '';
        $comment = isset($_POST['comment']) ? mysqli_real_escape_string($conn, substr($_POST['comment'], 0, 30)) : '';
        $status = mysqli_real_escape_string($conn, $_POST['status']);

        // Check if booking is for today and within 3 hours
        $today = date('Y-m-d');
        if ($booking_date == $today) {
            $current_time = date('H:i:s');
            $booking_timestamp = strtotime($booking_time);
            $current_timestamp = strtotime($current_time);
            $three_hours_later = $current_timestamp + (3 * 60 * 60);

            if ($booking_timestamp < $three_hours_later) {
                $errors = "Cannot book appointments less than 3 hours from now for today.";
            }
        }

        // Check for existing appointment at same time
        $check_query = "SELECT * FROM appointment 
                        WHERE booking_date = '$booking_date' 
                        AND booking_time = '$booking_time'
                        AND status !='cancelled'";
        $check_result = mysqli_query($conn, $check_query);

        if (mysqli_num_rows($check_result) > 0) {
            $errors = "An appointment already exists at this time.";
        }

        if (empty($errors)) {
            // Generate new appointment ID
            $appointment_id = generateAppointmentID($conn);
            $created_at = date('Y-m-d H:i:s');

            // Prepare INSERT query
            $insert_query = "INSERT INTO appointment 
                            (appointment_id, patient_id, service_type_id, 
                             booking_time, booking_date, symptoms, comment, status, created_at) 
                            VALUES ('$appointment_id', '$patient_id', 
                                    '$service_type_id', '$booking_time', '$booking_date', 
                                    '$symptoms', '$comment', '$status', '$created_at')";

            if (mysqli_query($conn, $insert_query)) {
                $message = "Appointment added successfully!";
                $appointment_id = $patient_id = $service_type_id = $booking_time =
                    $booking_date = $symptoms = $comment = $status = '';
            } else {
                $errors = "Error adding appointment: " . mysqli_error($conn);
            }
        }
    }

    // Update functionality
    if (isset($_POST['update_button']) && !empty($_POST['appointment_id'])) {
        $appointment_id = mysqli_real_escape_string($conn, $_POST['appointment_id']);
        $comment = isset($_POST['comment']) ? mysqli_real_escape_string($conn, substr($_POST['comment'], 0, 30)) : '';
        $status = mysqli_real_escape_string($conn, $_POST['status']);

        // Only update comment and status
        $update_query = "UPDATE appointment 
                    SET comment = '$comment',
                        status = '$status'
                    WHERE appointment_id = '$appointment_id'";

        if (mysqli_query($conn, $update_query)) {
            $message = "Appointment feedback updated successfully!";
            // Clear form fields
            $appointment_id = $comment = $status = '';
        } else {
            $errors = "Error updating appointment: " . mysqli_error($conn);
        }
    }
}

// Delete functionality
if (isset($_POST['delete_button'])) {
    // Only need appointment_id for delete
    if (!empty($appointment_id)) {
        // Prepare DELETE query
        $delete_query = "DELETE FROM appointment WHERE appointment_id = '$appointment_id'";

        if (mysqli_query($conn, $delete_query)) {
            $message = "Appointment deleted successfully!";
        } else {
            $errors = "Error deleting appointment: " . mysqli_error($conn);
        }
    } else {
        $errors = "No appointment selected for deletion";
    }
}

// Fetch patients and service types (same as original)
$patients = [];
$patients_query = "SELECT patient_id, first_name, last_name FROM patient ORDER BY first_name, last_name";
$patients_result = mysqli_query($conn, $patients_query);
while ($row = mysqli_fetch_assoc($patients_result)) {
    $patients[$row['patient_id']] = $row['patient_id'] . ' - ' . $row['first_name'] . ' ' . $row['last_name'];
}

$service_types = [];
$service_types_query = "SELECT service_type_id, service_name FROM service_type";
$service_types_result = mysqli_query($conn, $service_types_query);
while ($row = mysqli_fetch_assoc($service_types_result)) {
    $service_types[$row['service_type_id']] = $row['service_name'];
}

// Fetch patients and service types (same as original)
$patients = [];
$patients_query = "SELECT patient_id, first_name, last_name FROM patient ORDER BY first_name, last_name";
$patients_result = mysqli_query($conn, $patients_query);
while ($row = mysqli_fetch_assoc($patients_result)) {
    $patients[$row['patient_id']] = $row['patient_id'] . ' - ' . $row['first_name'] . ' ' . $row['last_name'];
}

$service_types = [];
$service_types_query = "SELECT service_type_id, service_name FROM service_type";
$service_types_result = mysqli_query($conn, $service_types_query);
while ($row = mysqli_fetch_assoc($service_types_result)) {
    $service_types[$row['service_type_id']] = $row['service_name'];
}

// Search functionality with status filter
$search_query = "";
$search_results = [];
$no_results_message = "";
$is_search = false;

$status_filter = "";
if (isset($_GET['status'])) {
    if ($_GET['status'] != 'all') {
        $status_filter = " AND a.status = '" . mysqli_real_escape_string($conn, $_GET['status']) . "'";
    }
}

// Modify the search functionality section to include date filtering
$date_filter = "";
if (isset($_GET['filter_date']) && !empty($_GET['filter_date'])) {
    $date_filter = " AND a.booking_date = '" . mysqli_real_escape_string($conn, $_GET['filter_date']) . "'";
}

// Then modify the search query to include the date filter
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $is_search = true;
    $search_term = mysqli_real_escape_string($conn, $_GET['search']);
    $search_query = "SELECT a.*, p.first_name, p.last_name, s.service_name
                     FROM appointment a
                     JOIN patient p ON a.patient_id = p.patient_id
                     JOIN service_type s ON a.service_type_id = s.service_type_id
                     WHERE (a.appointment_id LIKE '%$search_term%' 
                     OR p.first_name LIKE '%$search_term%' 
                     OR p.last_name LIKE '%$search_term%' 
                     OR s.service_name LIKE '%$search_term%'
                     OR a.booking_date LIKE '%$search_term%')
                     $status_filter
                     $date_filter";
    $search_result = mysqli_query($conn, $search_query);

    if ($search_result) {
        while ($row = mysqli_fetch_assoc($search_result)) {
            $search_results[] = $row;
        }

        if (empty($search_results)) {
            $no_results_message = "No results found for: '" . htmlspecialchars($_GET['search']) . "'";
        }
    }
}

// Also modify the all appointments query
if (!$is_search && empty($search_results)) {
    $all_appointments_query = "SELECT a.*, p.first_name, p.last_name, s.service_name
                              FROM appointment a
                              JOIN patient p ON a.patient_id = p.patient_id
                              JOIN service_type s ON a.service_type_id = s.service_type_id
                              WHERE 1=1 $status_filter $date_filter
                              ORDER BY a.booking_date DESC, a.booking_time DESC";
    $all_appointments_result = mysqli_query($conn, $all_appointments_query);

    while ($row = mysqli_fetch_assoc($all_appointments_result)) {
        $search_results[] = $row;
    }
}

// NEW: Get current date and time for JavaScript
$current_date = date('Y-m-d');
$current_time = date('H:i:s');

error_log("POST data: " . print_r($_POST, true));

// // Get counts for each status
// $status_counts = [
//     'all' => 0,
//     'scheduled' => 0,
//     'completed' => 0,
//     'cancelled' => 0,
//     'no_show' => 0
// ];

// // Query to get counts for each status
// $count_query = "SELECT status, COUNT(*) as count FROM appointment GROUP BY status";
// $count_result = mysqli_query($conn, $count_query);

// while ($row = mysqli_fetch_assoc($count_result)) {
//     $status_counts[$row['status']] = $row['count'];
// }

// // Calculate total count
// $status_counts['all'] = array_sum($status_counts) - $status_counts['all']; // Subtract the initial 0
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Management</title>
    <link rel="stylesheet" href="patient_management.css">
    <link rel="icon" href="../images/logo.svg" type="image/svg+xml">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Phetsarath:wght@400;700&display=swap" rel="stylesheet">

    <!-- CSS styles (same as original) -->
    <style>
        .readonly-field {
            background-color: #f5f5f5;
            color: #666;
            cursor: not-allowed;
        }

        .custom-dropdown {
            position: relative;
            width: 100%;
        }

        .dropdown-input {
            width: calc(100% - 24px);
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-size: 14px;
            background-color: white;
            cursor: pointer;
        }

        .dropdown-options {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 3px 3px;
            background: white;
            z-index: 1000;
            display: none;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .dropdown-options.show {
            display: block;
        }

        .dropdown-option {
            padding: 10px 15px;
            cursor: pointer;
            transition: background-color 0.2s;
            background-color: white;
        }

        .dropdown-option:hover {
            background-color: #3f51b5 !important;
            color: white !important;
        }

        .hidden-select {
            display: none;
        }

        textarea {
            max-width: 100%;
            max-height: 100px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 60%;
            border-radius: 5px;
            box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2);
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .modal-actions button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .modal-actions .update-button {
            background-color: #1976d2;
            color: white;
        }

        .modal-actions .cancel-button {
            background-color: #f44336;
            color: white;
        }

        .delete-link {
            color: #d32f2f;
            text-decoration: none;
            cursor: pointer;
        }

        .delete-link:hover {
            text-decoration: underline;
        }

        .status-filter-bar {
            display: flex;
            align-items: center;
            margin: 20px 0;
            border-bottom: 1px solid #ddd;
            flex-wrap: wrap;
        }

        .status-filter-bar .date-filter {
            margin-right: 20px;
        }

        .status-filter-bar .date-filter input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Phetsarath', sans-serif;
        }

        .status-filter-bar a {
            padding: 10px 15px;
            margin-right: 5px;
            text-decoration: none;
            color: #333;
            border-radius: 4px 4px 0 0;
            transition: all 0.3s ease;
        }

        .status-filter-bar a:hover {
            background-color: #f0f0f0;
        }

        .status-filter-bar a.active {
            background-color: #3f51b5;
            color: white;
            border-bottom: 2px solid #3f51b5;
        }

        /* Add this to your existing CSS */
        .status-filter-bar .date-filter {
            display: flex;
            align-items: center;
            margin-right: 20px;
        }

        .status-filter-bar .date-filter input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Phetsarath', sans-serif;
        }

        .status-filter-bar .date-filter button {
            padding: 8px 12px;
            margin-left: 5px;
            background-color: #3f51b5;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-family: 'Phetsarath', sans-serif;
        }

        .status-filter-bar .date-filter button:hover {
            background-color: #303f9f;
        }
    </style>
</head>

<body>
    <aside class="sidebar">
        <!-- Sidebar content remains the same as in the original HTML -->
        <!-- ... (previous sidebar code) ... -->
        <div class="logo">
        <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                    <path d="M12 4a4 4 0 100 8 4 4 0 000-8zM2 12C2 6.486 6.486 2 12 2s10 4.486 10 10-4.486 10-10 10S2 17.514 2 12z"></path>
                </svg>
            Vision Care
        </div>

        <ul class="menu">

            <!-- NEW STAFF INFO SECTION -->
            <li>
                <a href="admin_profile.php">
                    <div class="staff-info">
                        <svg xmlns="http://www.w3.org/2000/svg" style="color: #2c3e50;" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-user-round-icon lucide-circle-user-round">
                            <path d="M18 20a6 6 0 0 0-12 0" />
                            <circle cx="12" cy="10" r="4" />
                            <circle cx="12" cy="12" r="10" />
                        </svg>
                        <p><?php echo htmlspecialchars($_SESSION['admin_user_name']); ?></p>
                    </div>
                </a>
            </li>

            <li><a href="#">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="3" y1="9" x2="21" y2="9"></line>
                        <line x1="9" y1="21" x2="9" y2="9"></line>
                    </svg>
                    Dashboard</a></li>

            <!-- <li class="active"><a href="patient_management.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        ຂໍ້ມູນຄົນເຈັບ</a></li> -->

            <li class="has-submenu">
                <a href="#" onclick="toggleSubmenu(this)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-album-icon lucide-album">
                        <rect width="18" height="18" x="3" y="3" rx="2" ry="2" />
                        <polyline points="11 3 11 11 14 8 17 11 17 3" />
                    </svg>
                    ຈັດການຂໍ້ມູນພື້ນຖານ
                    <svg class="chevron" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </a>
                <ul class="submenu">
                    <li <?php echo basename($_SERVER['PHP_SELF']) == 'patient_management.php' ? 'class="active"' : ''; ?>><a href="patient_management.php">ຂໍ້ມູນຄົນເຈັບ</a></li>
                    <li <?php echo basename($_SERVER['PHP_SELF']) == 'service_type_managment.php' ? 'class="active"' : ''; ?>><a href="service_type_managment.php">ຂໍ້ມູນປະເພດບໍລິການ</a></li>
                    <li <?php echo basename($_SERVER['PHP_SELF']) == 'disease_management.php' ? 'class="active"' : ''; ?>><a href="disease_management.php">ຂໍ້ມູນພະຍາດ</a></li>
                    <li <?php echo basename($_SERVER['PHP_SELF']) == 'staff_management.php' ? 'class="active"' : ''; ?>><a href="staff_management.php">ຂໍ້ມູນພະນັກງານ</a></li>
                </ul>
            </li>

            <li><a href="reception_management.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-notebook-text-icon lucide-notebook-text">
                        <path d="M2 6h4" />
                        <path d="M2 10h4" />
                        <path d="M2 14h4" />
                        <path d="M2 18h4" />
                        <rect width="16" height="20" x="4" y="2" rx="2" />
                        <path d="M9.5 8h5" />
                        <path d="M9.5 12H16" />
                        <path d="M9.5 16H14" />
                    </svg>
                    ຕ້ອນຮັບ</a></li>

            <!-- <li><a href="staff_management.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        ຂໍ້ມູນພະນັກງານ</a></li> -->

            <li class="active"><a href="appointment_management.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    ຈັດການຈອງຄິວ</a></li>

            <!-- <li><a href="service_type_managment.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                        ປະເພດບໍລິການ</a></li> -->

            <!-- <li><a href="disease_management.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                        ຂໍ້ມູນພະຍາດ</a></li> -->

            <li><a href="checkup_management.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.42 4.58a5.4 5.4 0 0 0-7.65 0l-.77.78-.77-.78a5.4 5.4 0 0 0-7.65 0C1.46 6.7 1.33 10.28 4 13l8 8 8-8c2.67-2.72 2.54-6.3.42-8.42z"></path>
                    </svg>
                    ກວດເບື້ອງຕົ້ນ</a></li>

            <li><a href="treatment_management.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                    </svg>
                    ກວດຮັກສາ</a></li>

            <li><a href="eyes_check_management.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    ວັດແທກສາຍຕາ</a>

            <li><a href="receipt.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                    ໃບບິນເກັບເງິນ</a></li>

            <li class="has-submenu">
                <a href="#" onclick="toggleSubmenu(this)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                    ລາຍງານ
                    <svg class="chevron" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </a>
                <ul class="submenu">
                    <li <?php echo basename($_SERVER['PHP_SELF']) == 'report/patient_report.php' ? 'class="active"' : ''; ?>><a href="report/patient_report.php">ລາຍງານຄົນເຈັບ</a></li>
                    <li <?php echo basename($_SERVER['PHP_SELF']) == 'report/staff_report.php' ? 'class="active"' : ''; ?>><a href="report/staff_report.php">ລາຍງານພະນັກງານ</a></li>
                    <li <?php echo basename($_SERVER['PHP_SELF']) == 'report/income_report.php' ? 'class="active"' : ''; ?>><a href="report/income_report.php">ລາຍງານລາຍຮັບ</a></li>
                    <li <?php echo basename($_SERVER['PHP_SELF']) == 'report/disease_report.php' ? 'class="active"' : ''; ?>><a href="report/disease_report.php">ລາຍງານພະຍາດ</a></li>
                </ul>
            </li>

            <li><a href="logout.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-out-icon lucide-log-out">
                        <path d="m16 17 5-5-5-5" />
                        <path d="M21 12H9" />
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                    </svg>
                    ອອກຈາກລະບົບ</a></li>

            </li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="header">
            <h1>Appointment Management</h1>
            <button class="new-patient-button" name="new_appointment" onclick="clearForm()">+ New Appointment</button>
        </div>

        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($errors): ?>
            <div class="error"><?php echo $errors; ?></div>
        <?php endif; ?>

        <!-- Appointment Form -->
        <form method="POST" action="" id="appointmentForm">
            <div class="patient-form">
                <div class="form-group">
                    <label for="appointmentID">Appointment ID</label>
                    <input type="text" id="appointmentID" name="appointment_id" readonly>
                </div>

                <div class="form-group">
                    <label for="patient_id">Patient</label>
                    <div class="custom-dropdown">
                        <input type="text" id="patient_search" class="dropdown-input" placeholder="Type a name..." autocomplete="off">
                        <select id="patient_id" name="patient_id" class="hidden-select" required>
                            <option value="">Select Patient</option>
                            <?php foreach ($patients as $id => $name): ?>
                                <option value="<?php echo htmlspecialchars($id); ?>"><?php echo htmlspecialchars($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div id="patient_options" class="dropdown-options">
                            <?php foreach ($patients as $id => $name): ?>
                                <div class="dropdown-option" data-value="<?php echo htmlspecialchars($id); ?>"><?php echo htmlspecialchars($name); ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="service_type_id">Service Type</label>
                    <select id="service_type_id" name="service_type_id" required>
                        <option value="">Select Service</option>
                        <?php foreach ($service_types as $id => $name): ?>
                            <option value="<?php echo htmlspecialchars($id); ?>"><?php echo htmlspecialchars($name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="booking_date">Date</label>
                    <input type="date" id="booking_date" name="booking_date" required>
                    <div id="dateError" class="error-message" style="display: none;"></div>
                </div>

                <div class="form-group">
                    <label for="booking_time">Time</label>
                    <select id="booking_time" name="booking_time" required>
                        <option value="">Select Time</option>
                        <?php foreach ($time_slots as $time): ?>
                            <option value="<?php echo htmlspecialchars($time); ?>"><?php echo htmlspecialchars(substr($time, 0, 5)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="symptoms">Symptoms (max 30 chars)</label>
                    <textarea id="symptoms" name="symptoms" rows="3" maxlength="30"></textarea>
                </div>

                <div class="form-group">
                    <label for="comment">Comment (max 30 chars)</label>
                    <textarea id="comment" name="comment" rows="3" maxlength="30"></textarea>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="scheduled" selected>Scheduled</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="no_show">No-show</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="created_at">Created At</label>
                    <input type="text" id="created_at" name="created_at" readonly class="readonly-field">
                </div>

                <div class="form-actions">
                    <button type="submit" class="save-button" name="save_button" id="saveButton">Save</button>
                    <button type="submit" class="update-button" name="update_button" id="updateButton">Update</button>
                    <button type="submit" class="delete-button" name="delete_button" id="deleteButton">Delete</button>
                </div>
            </div>
        </form>

        <!-- Search and Table (same as original - truncated) -->
        <div class="patient-list-header">
            <form method="GET" action="">
                <input type="search" name="search" placeholder="Search appointments..."
                    value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit">Search</button>
            </form>
        </div>

        <!-- Find the status-filter-bar div and add the date filter at the beginning -->
        <div class="status-filter-bar">
            <div class="date-filter">
                <input type="date" id="filter_date" name="filter_date" value="<?php echo isset($_GET['filter_date']) ? htmlspecialchars($_GET['filter_date']) : ''; ?>">
                <button type="button" onclick="applyDateFilter()">Filter</button>
                <?php if (isset($_GET['filter_date'])): ?>
                    <button type="button" onclick="clearDateFilter()" style="margin-left: 5px;">Clear</button>
                <?php endif; ?>
            </div>

            <a href="?status=all<?php echo isset($_GET['filter_date']) ? '&filter_date=' . htmlspecialchars($_GET['filter_date']) : ''; ?>" class="<?php echo (!isset($_GET['status'])) || $_GET['status'] == 'all' ? 'active' : ''; ?>">
                All Appointments <span class="status-count"></span>
            </a>
            <a href="?status=scheduled<?php echo isset($_GET['filter_date']) ? '&filter_date=' . htmlspecialchars($_GET['filter_date']) : ''; ?>" class="<?php echo (isset($_GET['status'])) && $_GET['status'] == 'scheduled' ? 'active' : ''; ?>">
                Scheduled <span class="status-count"></span>
            </a>
            <a href="?status=completed<?php echo isset($_GET['filter_date']) ? '&filter_date=' . htmlspecialchars($_GET['filter_date']) : ''; ?>" class="<?php echo (isset($_GET['status'])) && $_GET['status'] == 'completed' ? 'active' : ''; ?>">
                Completed <span class="status-count"></span>
            </a>
            <a href="?status=cancelled<?php echo isset($_GET['filter_date']) ? '&filter_date=' . htmlspecialchars($_GET['filter_date']) : ''; ?>" class="<?php echo (isset($_GET['status'])) && $_GET['status'] == 'cancelled' ? 'active' : ''; ?>">
                Cancelled <span class="status-count"></span>
            </a>
            <a href="?status=no_show<?php echo isset($_GET['filter_date']) ? '&filter_date=' . htmlspecialchars($_GET['filter_date']) : ''; ?>" class="<?php echo (isset($_GET['status'])) && $_GET['status'] == 'no_show' ? 'active' : ''; ?>">
                No-show <span class="status-count"></span>
            </a>
        </div>

        <!-- Table and modals (same structure - truncated for brevity) -->
        <?php if (!empty($no_results_message)): ?>
            <div class="alert alert-info">
                <?php echo $no_results_message; ?>
            </div>
        <?php endif; ?>

        <!-- Appointment Table -->
        <table class="patient-table">
            <?php if (!empty($search_results)): ?>
                <thead>
                    <tr>
                        <th>APPOINTMENT ID</th>
                        <th>PATIENT</th>
                        <th>SERVICE</th>
                        <th>DATE</th>
                        <th>TIME</th>
                        <th>STATUS</th>
                        <th>CREATED AT</th>
                        <th>ACTIONS</th>
                        <th>DELETE</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($search_results as $appointment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($appointment['appointment_id']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['service_name']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['booking_date']); ?></td>
                            <td><?php echo htmlspecialchars(substr($appointment['booking_time'], 0, 5)); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($appointment['status'])); ?></td>
                            <td><?php echo htmlspecialchars($appointment['created_at']); ?></td>
                            <td>
                                <a href="#" onclick="showEditModal(
                                        '<?php echo htmlspecialchars($appointment['appointment_id']); ?>',
                                        '<?php echo htmlspecialchars($appointment['patient_id']); ?>',
                                        '<?php echo htmlspecialchars($appointment['service_type_id']); ?>',
                                        '<?php echo htmlspecialchars($appointment['booking_time']); ?>',
                                        '<?php echo htmlspecialchars($appointment['booking_date']); ?>',
                                        '<?php echo htmlspecialchars($appointment['symptoms']); ?>',
                                        '<?php echo htmlspecialchars($appointment['comment']); ?>',
                                        '<?php echo htmlspecialchars($appointment['status']); ?>'
                                    )">Edit</a>
                            </td>
                            <td>
                                <a href="#" class="delete-link" onclick="confirmDelete('<?php echo htmlspecialchars($appointment['appointment_id']); ?>')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            <?php endif; ?>
        </table>
    </main>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h2>Appointment Feedback</h2>
            <!-- <form id="editForm" method="POST" action="appointment_management.php"> -->
            <!-- In your edit modal form, change the action to include current filters -->
            <form id="editForm" method="POST" action="appointment_management.php?<?php echo isset($_GET['status']) ? 'status=' . htmlspecialchars($_GET['status']) : ''; ?><?php echo isset($_GET['filter_date']) ? '&filter_date=' . htmlspecialchars($_GET['filter_date']) : ''; ?><?php echo isset($_GET['search']) ? '&search=' . htmlspecialchars($_GET['search']) : ''; ?>">
                <input type="hidden" id="modal_appointment_id" name="appointment_id">
                <input type="hidden" id="modal_hidden_patient_id" name="patient_id">
                <div class="patient-form">
                    <!-- Appointment ID field (readonly) -->
                    <div class="form-group">
                        <label for="display_appointment_id">Appointment ID</label>
                        <input type="text" id="display_appointment_id" name="display_appointment_id" readonly class="readonly-field">
                    </div>

                    <!-- Patient field (readonly) -->
                    <div class="form-group">
                        <label for="modal_patient_id">Patient</label>
                        <input type="text" id="modal_patient_display" class="readonly-field" readonly>
                        <select id="modal_patient_id" name="patient_id" class="hidden-select" disabled>
                            <option value="">Select Patient</option>
                            <?php foreach ($patients as $id => $name): ?>
                                <option value="<?php echo htmlspecialchars($id); ?>"><?php echo htmlspecialchars($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Service Type (readonly) -->
                    <div class="form-group">
                        <label for="modal_service_type_display">Service Type</label>
                        <input type="text" id="modal_service_type_display" class="readonly-field" readonly>
                        <select id="modal_service_type_id" name="service_type_id" class="hidden-select" disabled>
                            <option value="">Select Service</option>
                            <?php foreach ($service_types as $id => $name): ?>
                                <option value="<?php echo htmlspecialchars($id); ?>"><?php echo htmlspecialchars($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Date (readonly) -->
                    <div class="form-group">
                        <label for="modal_booking_date_display">Date</label>
                        <input type="text" id="modal_booking_date_display" class="readonly-field" readonly>
                        <input type="date" id="modal_booking_date" name="booking_date" class="hidden-select" disabled>
                    </div>

                    <!-- Time (readonly) -->
                    <div class="form-group">
                        <label for="modal_booking_time_display">Time</label>
                        <input type="text" id="modal_booking_time_display" class="readonly-field" readonly>
                        <select id="modal_booking_time" name="booking_time" class="hidden-select" disabled>
                            <option value="">Select Time</option>
                            <?php foreach ($time_slots as $time): ?>
                                <option value="<?php echo htmlspecialchars($time); ?>"><?php echo htmlspecialchars(substr($time, 0, 5)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Symptoms (readonly) -->
                    <div class="form-group">
                        <label for="modal_symptoms_display">Symptoms</label>
                        <textarea id="modal_symptoms_display" class="readonly-field" rows="3" readonly></textarea>
                        <textarea id="modal_symptoms" name="symptoms" class="hidden-select" rows="3" maxlength="30" disabled></textarea>
                    </div>

                    <!-- Comment (editable) -->
                    <div class="form-group">
                        <label for="modal_comment">Comment (max 30 chars)</label>
                        <textarea id="modal_comment" name="comment" rows="3" maxlength="30"></textarea>
                    </div>

                    <!-- Status (editable) -->
                    <div class="form-group">
                        <label for="modal_status">Status</label>
                        <select id="modal_status" name="status" required>
                            <option value="scheduled">Scheduled</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="no_show">No-show</option>
                        </select>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="cancel-button" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="update-button" name="update_button">Update Feedback</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content" style="width: 40%;">
            <h2>Confirm Delete</h2>
            <p>Are you sure you want to delete this appointment?</p>
            <form id="deleteForm" method="POST" action="">
                <input type="hidden" id="delete_appointment_id" name="appointment_id">
                <div class="modal-actions">
                    <button type="button" class="cancel-button" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="delete-button" name="delete_button">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // NEW: Add current date and time variables for JavaScript
        const currentDate = '<?php echo htmlspecialchars($current_date); ?>';
        const currentTime = '<?php echo htmlspecialchars($current_time); ?>';

        // Custom dropdown functionality (same as original)
        document.addEventListener('DOMContentLoaded', function() {
            const patientSearch = document.getElementById('patient_search');
            const patientOptions = document.getElementById('patient_options');
            const hiddenSelect = document.getElementById('patient_id');
            const dropdownOptions = patientOptions.querySelectorAll('.dropdown-option');

            patientSearch.addEventListener('focus', function() {
                dropdownOptions.forEach(option => {
                    option.style.display = 'block';
                });
                patientOptions.classList.add('show');
            });

            function filterOptions() {
                const searchTerm = patientSearch.value.toLowerCase();
                let hasVisibleOptions = false;

                dropdownOptions.forEach(option => {
                    const optionText = option.textContent.toLowerCase();
                    if (optionText.includes(searchTerm)) {
                        option.style.display = 'block';
                        hasVisibleOptions = true;
                    } else {
                        option.style.display = 'none';
                    }
                });

                if (hasVisibleOptions || searchTerm.length === 0) {
                    patientOptions.classList.add('show');
                } else {
                    patientOptions.classList.remove('show');
                }
            }

            patientSearch.addEventListener('input', filterOptions);

            dropdownOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const value = this.getAttribute('data-value');
                    const text = this.textContent;

                    patientSearch.value = text;
                    hiddenSelect.value = value;
                    patientOptions.classList.remove('show');

                    dropdownOptions.forEach(opt => {
                        opt.classList.remove('selected');
                    });
                    this.classList.add('selected');
                });
            });

            document.addEventListener('click', function(e) {
                if (!e.target.closest('.custom-dropdown')) {
                    patientOptions.classList.remove('show');
                }
            });

            if (hiddenSelect.value) {
                const selectedOption = hiddenSelect.querySelector('option:checked');
                if (selectedOption) {
                    patientSearch.value = selectedOption.textContent;
                    const selectedDiv = patientOptions.querySelector(`.dropdown-option[data-value="${selectedOption.value}"]`);
                    if (selectedDiv) {
                        selectedDiv.classList.add('selected');
                    }
                }
            }
        });

        // Modal functions
        function showEditModal(appointmentId, patientId, serviceTypeId, bookingTime, bookingDate, symptoms, comment, status) {
            // Set the hidden appointment ID
            document.getElementById('modal_appointment_id').value = appointmentId;
            document.getElementById('display_appointment_id').value = appointmentId;

            // Set patient information (display only)
            const patientSelect = document.getElementById('modal_patient_id');
            patientSelect.value = patientId;
            document.getElementById('modal_hidden_patient_id').value = patientId;
            document.getElementById('modal_patient_display').value = patientSelect.options[patientSelect.selectedIndex].text;

            // Set service type (display only)
            const serviceSelect = document.getElementById('modal_service_type_id');
            serviceSelect.value = serviceTypeId;
            document.getElementById('modal_service_type_display').value = serviceSelect.options[serviceSelect.selectedIndex].text;

            // Set date and time (display only)
            document.getElementById('modal_booking_date').value = bookingDate;
            document.getElementById('modal_booking_date_display').value = bookingDate;
            document.getElementById('modal_booking_time').value = bookingTime;
            document.getElementById('modal_booking_time_display').value = bookingTime.substring(0, 5);

            // Set symptoms (display only)
            document.getElementById('modal_symptoms').value = symptoms || '';
            document.getElementById('modal_symptoms_display').value = symptoms || '';

            // Set editable fields
            document.getElementById('modal_comment').value = comment || '';
            document.getElementById('modal_status').value = status;

            document.getElementById('editModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function confirmDelete(appointmentId) {
            document.getElementById('delete_appointment_id').value = appointmentId;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('editModal')) {
                closeModal();
            }
            if (event.target == document.getElementById('deleteModal')) {
                closeDeleteModal();
            }
        }

        // MODIFIED: Enhanced time slot availability function with 3-hour buffer rule
        function updateTimeSlotsAvailability() {
            const bookingDate = document.getElementById('booking_date').value;
            const bookingTimeSelect = document.getElementById('booking_time');
            const modalBookingDate = document.getElementById('modal_booking_date');
            const modalBookingTimeSelect = document.getElementById('modal_booking_time');

            // Function to check if a time slot is within 3 hours from now
            function isWithin3Hours(timeSlot, selectedDate) {
                if (selectedDate !== currentDate) {
                    return false; // Not today, so no time restrictions
                }

                // Parse current time and selected time
                const now = new Date();
                const [currentHours, currentMinutes, currentSeconds] = currentTime.split(':').map(Number);
                now.setHours(currentHours, currentMinutes, currentSeconds);

                const [slotHours, slotMinutes, slotSeconds] = timeSlot.split(':').map(Number);
                const slotTime = new Date();
                slotTime.setHours(slotHours, slotMinutes, slotSeconds);

                // Calculate difference in milliseconds
                const diffMs = slotTime - now;
                const diffHours = diffMs / (1000 * 60 * 60);

                return diffHours < 3;
            }

            if (bookingDate) {
                // First, reset all options to default state
                Array.from(bookingTimeSelect.options).forEach(option => {
                    if (option.value) {
                        option.disabled = false;
                        option.style.color = '';
                        // Remove any status labels
                        option.textContent = option.textContent.replace(' (Booked)', '')
                            .replace(' (Unavailable)', '');
                    }
                });

                // Fetch booked time slots for this date via AJAX
                fetch('get_booked_times.php?date=' + bookingDate)
                    .then(response => response.json())
                    .then(bookedTimes => {
                        // Disable already booked times (unless status is cancelled)
                        bookedTimes.forEach(time => {
                            const option = bookingTimeSelect.querySelector(`option[value="${time.time}"]`);
                            if (option && time.status !== 'cancelled') {
                                option.disabled = true;
                                option.style.color = '#999';
                                option.textContent = option.textContent + ' (Booked)';
                                if (option.selected) {
                                    option.selected = false;
                                    bookingTimeSelect.selectedIndex = 0;
                                }
                            }
                        });

                        // NEW: Disable time slots within 3 hours if selected date is today
                        if (bookingDate === currentDate) {
                            Array.from(bookingTimeSelect.options).forEach(option => {
                                if (option.value && isWithin3Hours(option.value, bookingDate)) {
                                    option.disabled = true;
                                    option.style.color = '#ccc';
                                    option.textContent = option.textContent + ' (Unavailable)';
                                    if (option.selected) {
                                        option.selected = false;
                                        bookingTimeSelect.selectedIndex = 0;
                                    }
                                }
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching booked times:', error);
                    });
            }

            // Same logic for modal if it exists
            if (modalBookingDate && modalBookingDate.value) {
                // First reset modal options
                Array.from(modalBookingTimeSelect.options).forEach(option => {
                    if (option.value) {
                        option.disabled = false;
                        option.style.color = '';
                        option.textContent = option.textContent.replace(' (Booked)', '')
                            .replace(' (Unavailable)', '');
                    }
                });

                fetch('get_booked_times.php?date=' + modalBookingDate.value)
                    .then(response => response.json())
                    .then(bookedTimes => {
                        // Store current selected time before making changes
                        const currentSelectedTime = modalBookingTimeSelect.value;

                        bookedTimes.forEach(time => {
                            const option = modalBookingTimeSelect.querySelector(`option[value="${time.time}"]`);
                            if (option && option.value !== modalBookingTimeSelect.dataset.currentTime && time.status !== 'cancelled') {
                                option.disabled = true;
                                option.style.color = '#999';
                                option.textContent = option.textContent + ' (Booked)';
                            }
                        });

                        // NEW: Check if date is today and selected time is within 3 hours
                        if (modalBookingDate.value === currentDate) {
                            Array.from(modalBookingTimeSelect.options).forEach(option => {
                                if (option.value && isWithin3Hours(option.value, modalBookingDate.value)) {
                                    option.disabled = true;
                                    option.style.color = '#ccc';
                                    option.textContent = option.textContent + ' (Unavailable)';
                                }
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching booked times:', error);
                    });
            }
        }

        // Other functions (same as original)
        function clearForm() {
            document.getElementById('appointmentID').value = '';
            document.getElementById('patient_id').selectedIndex = 0;
            document.getElementById('patient_search').value = '';
            document.getElementById('service_type_id').selectedIndex = 0;
            document.getElementById('booking_time').selectedIndex = 0;
            document.getElementById('booking_date').value = '';
            document.getElementById('symptoms').value = '';
            document.getElementById('comment').value = '';
            document.getElementById('status').selectedIndex = 0;
            document.getElementById('created_at').value = '';

            const patientSearch = document.getElementById('patient_search');
            const patientOptions = document.getElementById('patient_options');

            const dropdownOptions = patientOptions.querySelectorAll('.dropdown-option');
            dropdownOptions.forEach(option => {
                option.style.display = 'block';
                option.classList.remove('selected');
            });

            patientOptions.classList.add('show');

            setTimeout(() => {
                patientSearch.focus();
            }, 10);

            document.getElementById('saveButton').disabled = false;
            document.getElementById('updateButton').disabled = true;
            document.getElementById('deleteButton').disabled = true;
        }

        // Add event listeners for date changes
        document.getElementById('booking_date')?.addEventListener('change', updateTimeSlotsAvailability);

        document.getElementById('modal_booking_date')?.addEventListener('change', function() {
            const modalBookingTimeSelect = document.getElementById('modal_booking_time');
            const currentSelectedTime = modalBookingTimeSelect.dataset.currentTime;

            // Reset time selection if date is changed (unless it's the original date)
            const originalDate = document.getElementById('modal_booking_date').dataset.originalDate;

            if (this.value !== originalDate) {
                modalBookingTimeSelect.selectedIndex = 0; // Reset to "Select Time"
                modalBookingTimeSelect.dataset.currentTime = ''; // Clear the stored current time
            }

            // If date is changed to today, check if we need to reset the time
            if (this.value === currentDate) {
                const now = new Date();
                const currentHours = String(now.getHours()).padStart(2, '0');
                const currentMinutes = String(now.getMinutes()).padStart(2, '0');
                const currentSeconds = String(now.getSeconds()).padStart(2, '0');
                const currentTimeFormatted = `${currentHours}:${currentMinutes}:${currentSeconds}`;

                if (modalBookingTimeSelect.value &&
                    modalBookingTimeSelect.value < currentTimeFormatted &&
                    modalBookingTimeSelect.value !== currentSelectedTime) {
                    modalBookingTimeSelect.selectedIndex = 0; // Reset to "Select Time"
                }
            }

            updateTimeSlotsAvailability();
        });
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Set min date to today
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0');
            const dd = String(today.getDate()).padStart(2, '0');
            const minDate = `${yyyy}-${mm}-${dd}`;
            document.getElementById('booking_date').setAttribute('min', minDate);
            document.getElementById('modal_booking_date')?.setAttribute('min', minDate);

            // Initialize button states
            document.getElementById('updateButton').disabled = true;
            document.getElementById('deleteButton').disabled = true;

            // Update time slots availability
            updateTimeSlotsAvailability();

            // For the edit modal, store the current time to avoid disabling it
            const modalBookingTimeSelect = document.getElementById('modal_booking_time');
            if (modalBookingTimeSelect) {
                modalBookingTimeSelect.dataset.currentTime = modalBookingTimeSelect.value;
            }
        });

        // Form submission validation (same as original)
        document.getElementById('appointmentForm').addEventListener('submit', function(e) {
            const appointmentId = document.getElementById('appointmentID').value;
            const isSave = e.submitter.name === 'save_button';
            const isUpdate = e.submitter.name === 'update_button';
            const isDelete = e.submitter.name === 'delete_button';

            if (isSave && appointmentId) {
                e.preventDefault();
                alert("Error: You're trying to save an existing record. Use Update instead.");
                return;
            }

            if ((isUpdate || isDelete) && !appointmentId) {
                e.preventDefault();
                alert("Error: No appointment selected. Please select an appointment to edit first.");
                return;
            }

            if (isDelete && !appointmentId) {
                e.preventDefault();
                alert("Error: No appointment selected for deletion");
                return;
            }

            const bookingDate = document.getElementById('booking_date').value;
            if (bookingDate) {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const selectedDate = new Date(bookingDate);

                if (selectedDate < today) {
                    e.preventDefault();
                    document.getElementById('dateError').textContent = "Date cannot be in the past";
                    document.getElementById('dateError').style.display = "block";
                    document.getElementById('booking_date').classList.add("error");
                    document.getElementById('booking_date').focus();
                    return;
                }
            }
        });

        document.getElementById('booking_date').addEventListener('change', function() {
            document.getElementById('dateError').style.display = "none";
            this.classList.remove("error");
        });

        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        function toggleSubmenu(element) {
            event.preventDefault();
            const parent = element.parentElement;
            const submenu = parent.querySelector('.submenu');

            // Toggle the visibility of the submenu
            submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';

            // Remove 'active' class from parent if submenu is being shown
            if (submenu.style.display === 'block') {
                parent.classList.remove('active');
            }
        }

        // function toggleSubmenu(element) {
        //     event.preventDefault();
        //     const parent = element.parentElement;
        //     parent.classList.toggle('active');
        // }

        document.addEventListener('DOMContentLoaded', function() {

            // Automatically expand submenu if current page is a submenu item
            const currentPage = window.location.pathname.split('/').pop();
            const menuItems = document.querySelectorAll('.has-submenu');

            menuItems.forEach(menuItem => {
                const submenuLinks = menuItem.querySelectorAll('.submenu a');
                let shouldExpand = false;

                submenuLinks.forEach(link => {
                    const linkPage = link.getAttribute('href').split('/').pop();
                    if (linkPage === currentPage) {
                        shouldExpand = true;
                    }
                });

                if (shouldExpand) {
                    const toggleLink = menuItem.querySelector('a[onclick]');
                    toggleSubmenu(toggleLink, true);
                }
            });
        });

        // // Add this to your script section
        // document.getElementById('filter_date').addEventListener('change', function() {
        //     const date = this.value;
        //     const currentUrl = new URL(window.location.href);

        //     // Keep existing parameters
        //     const params = new URLSearchParams(window.location.search);

        //     // Set or update the filter_date parameter
        //     params.set('filter_date', date);

        //     // Keep the status filter if it exists
        //     if (params.has('status')) {
        //         params.set('status', params.get('status'));
        //     }

        //     window.location.href = window.location.pathname + '?' + params.toString();
        // });

        // Add these functions to your script section
        function applyDateFilter() {
            const date = document.getElementById('filter_date').value;
            const currentUrl = new URL(window.location.href);

            // Keep existing parameters
            const params = new URLSearchParams(window.location.search);

            // Set or update the filter_date parameter
            if (date) {
                params.set('filter_date', date);
            } else {
                params.delete('filter_date');
            }

            // Keep the status filter if it exists
            if (!params.has('status')) {
                params.set('status', 'all');
            }

            window.location.href = window.location.pathname + '?' + params.toString();
        }

        function clearDateFilter() {
            const currentUrl = new URL(window.location.href);
            const params = new URLSearchParams(window.location.search);

            params.delete('filter_date');

            window.location.href = window.location.pathname + '?' + params.toString();
        }

        // Add event listener for Enter key on date filter
        document.getElementById('filter_date').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyDateFilter();
            }
        });
    </script>
</body>

</html>