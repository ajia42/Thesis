<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: signin_admin.php');
    exit();
}

// Security headers to prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Include database configuration
include("../db_config.php");

// Function to generate next appointment ID
function generateAppointmentID($conn)
{
    $sql = "SELECT MAX(appointment_id) AS max_id FROM appointment";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    // If no appointment exists, start with A0001
    if (empty($row['max_id'])) {
        return 'A0001';
    }

    // Extract the numeric part and increment
    $lastID = $row['max_id'];
    $numPart = intval(substr($lastID, 1));
    $newNumPart = $numPart + 1;

    // Format the new ID with leading zeros
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
    '15:30:00'
];

// Validation checks
$errors = '';
$message = '';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
    $service_type_id = mysqli_real_escape_string($conn, $_POST['service_type_id']);
    $booking_time = mysqli_real_escape_string($conn, $_POST['booking_time']);
    $booking_date = mysqli_real_escape_string($conn, $_POST['booking_date']);
    $symptoms = mysqli_real_escape_string($conn, $_POST['symptoms']);
    $comment = mysqli_real_escape_string($conn, $_POST['comment']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $admin_id = $_SESSION['admin_id']; // Get admin_id from session

    // Action based on button click
    if (isset($_POST['save_button'])) {
        // Check for existing appointment at same time
        $check_query = "SELECT * FROM appointment 
                        WHERE booking_date = '$booking_date' 
                        AND booking_time = '$booking_time'";
        $check_result = mysqli_query($conn, $check_query);

        if (mysqli_num_rows($check_result) > 0) {
            $errors = "An appointment already exists at this time.";
        }

        if (empty($errors)) {
            // Generate new appointment ID
            $appointment_id = generateAppointmentID($conn);

            // Prepare INSERT query
            $insert_query = "INSERT INTO appointment 
                            (appointment_id, patient_id, admin_id, service_type_id, 
                             booking_time, booking_date, symptoms, comment, status) 
                            VALUES ('$appointment_id', '$patient_id', '$admin_id', 
                                    '$service_type_id', '$booking_time', '$booking_date', 
                                    '$symptoms', '$comment', '$status')";

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

        // Check for existing appointment at same time (excluding current appointment)
        $check_query = "SELECT * FROM appointment 
                        WHERE booking_date = '$booking_date' 
                        AND booking_time = '$booking_time'
                        AND appointment_id != '$appointment_id'";
        $check_result = mysqli_query($conn, $check_query);

        if (mysqli_num_rows($check_result) > 0) {
            $errors = "An appointment already exists at this time.";
        }

        if (empty($errors)) {
            // Prepare UPDATE query
            $update_query = "UPDATE appointment 
                            SET patient_id = '$patient_id',
                                service_type_id = '$service_type_id',
                                booking_time = '$booking_time',
                                booking_date = '$booking_date',
                                symptoms = '$symptoms',
                                comment = '$comment',
                                status = '$status'
                            WHERE appointment_id = '$appointment_id'";

            if (mysqli_query($conn, $update_query)) {
                $message = "Appointment updated successfully!";
                $appointment_id = $patient_id = $service_type_id = $booking_time =
                    $booking_date = $symptoms = $comment = $status = '';
            } else {
                $errors = "Error updating appointment: " . mysqli_error($conn);
            }
        }
    }

    // Delete functionality
    if (isset($_POST['delete_button']) && !empty($_POST['appointment_id'])) {
        $appointment_id = mysqli_real_escape_string($conn, $_POST['appointment_id']);

        // Prepare DELETE query
        $delete_query = "DELETE FROM appointment WHERE appointment_id = '$appointment_id'";

        if (mysqli_query($conn, $delete_query)) {
            $message = "Appointment deleted successfully!";
        } else {
            $errors = "Error deleting appointment: " . mysqli_error($conn);
        }
    }
}

// Fetch all patients for dropdown with ID and name
$patients = [];
$patients_query = "SELECT patient_id, first_name, last_name FROM patient ORDER BY first_name, last_name";
$patients_result = mysqli_query($conn, $patients_query);
while ($row = mysqli_fetch_assoc($patients_result)) {
    $patients[$row['patient_id']] = $row['patient_id'] . ' - ' . $row['first_name'] . ' ' . $row['last_name'];
}

// Fetch all service types for dropdown
$service_types = [];
$service_types_query = "SELECT service_type_id, service_name FROM service_type";
$service_types_result = mysqli_query($conn, $service_types_query);
while ($row = mysqli_fetch_assoc($service_types_result)) {
    $service_types[$row['service_type_id']] = $row['service_name'];
}

// Fetch admin details for display
$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['staff_name'];

// Search functionality
$search_query = "";
$search_results = [];
$no_results_message = "";
$is_search = false;

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $is_search = true;
    $search_term = mysqli_real_escape_string($conn, $_GET['search']);
    $search_query = "SELECT a.*, p.first_name, p.last_name, s.service_name,  CONCAT(ad.first_name, ' ', ad.last_name) AS admin_name
                     FROM appointment a
                     JOIN patient p ON a.patient_id = p.patient_id
                     JOIN service_type s ON a.service_type_id = s.service_type_id
                     JOIN admin ad ON a.admin_id = ad.admin_id
                     WHERE a.appointment_id LIKE '%$search_term%' 
                     OR p.first_name LIKE '%$search_term%' 
                     OR p.last_name LIKE '%$search_term%' 
                     OR s.service_name LIKE '%$search_term%'
                     OR a.booking_date LIKE '%$search_term%'
                     OR  CONCAT(ad.first_name, ' ', ad.last_name) LIKE '%$search_term%'";
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

// Fetch all appointments only if no search is performed
if (!$is_search && empty($search_results)) {
    $all_appointments_query = "SELECT a.*, p.first_name, p.last_name, s.service_name, CONCAT(ad.first_name, ' ', ad.last_name) AS admin_name
                              FROM appointment a
                              JOIN patient p ON a.patient_id = p.patient_id
                              JOIN service_type s ON a.service_type_id = s.service_type_id
                              JOIN admin ad ON a.admin_id = ad.admin_id";
    $all_appointments_result = mysqli_query($conn, $all_appointments_query);

    while ($row = mysqli_fetch_assoc($all_appointments_result)) {
        $search_results[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Management</title>
    <link rel="stylesheet" href="patient_management.css">

    <style>
        .readonly-field {
            background-color: #f5f5f5;
            color: #666;
            cursor: not-allowed;
        }

        .custom-dropdown {
            position: relative;
            width: 100%;
            /* Keep full width of parent container */
        }

        .dropdown-input {
            width: calc(100% - 24px);
            /* Account for padding */
            padding: 10px 12px;
            /* Match patient_management.css padding */
            border: 1px solid #ddd;
            border-radius: 3px;
            /* Match other inputs */
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
            /* Match other inputs */
            background: white;
            z-index: 1000;
            display: none;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            /* Match patient_management.css shadow */
        }

        .dropdown-options.show {
            display: block;
            border: 1px solid black;
            /* Temporary for debugging */

        }

        .dropdown-option {
            padding: 10px 15px;
            /* Match patient_management.css padding */
            cursor: pointer;
            transition: background-color 0.2s;
            background-color: white;
        }

        .dropdown-option:hover {
            background-color: #3f51b5 !important;
            color: white !important;
        }

        .dropdown-option.selected {
            background-color: #e1f0ff;
            color: #000;
        }

        .hidden-select {
            display: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
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
                            <p><?php echo htmlspecialchars($_SESSION['staff_name']); ?></p>
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

                <li><a href="patient_management.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        Patients</a></li>

                <li><a href="staff_management.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        Staff</a></li>

                <li class="active"><a href="appointment_management.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        Appointments</a></li>

                <li><a href="service_type_managment.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                        Services</a></li>

                <li><a href="disease_management.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                        Diseases</a></li>

                <li><a href="checkup_management.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20.42 4.58a5.4 5.4 0 0 0-7.65 0l-.77.78-.77-.78a5.4 5.4 0 0 0-7.65 0C1.46 6.7 1.33 10.28 4 13l8 8 8-8c2.67-2.72 2.54-6.3.42-8.42z"></path>
                        </svg>
                        General Checkups</a></li>

                <li><a href="treatment_management.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                        </svg>
                        Treatments</a></li>

                <li><a href="eyes_check_management.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        Eyes Check</a>

                <li><a href="receipt.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                        Receipts</a></li>

                <li><a href="#">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="20" x2="18" y2="10"></line>
                            <line x1="12" y1="20" x2="12" y2="4"></line>
                            <line x1="6" y1="20" x2="6" y2="14"></line>
                        </svg>
                        Reports</a></li>

                <li><a href="logout.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-out-icon lucide-log-out">
                            <path d="m16 17 5-5-5-5" />
                            <path d="M21 12H9" />
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                        </svg>
                        Log out</a></li>

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

                    <!-- Admin ID Field (readonly) -->
                    <div class="form-group">
                        <label for="admin_id">Admin</label>
                        <input type="text" id="admin_id" name="admin_id" class="readonly-field"
                            value="<?php echo htmlspecialchars($admin_id . ' - ' . $admin_name); ?>" readonly>
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
                        <label for="booking_time">Time</label>
                        <select id="booking_time" name="booking_time" required>
                            <option value="">Select Time</option>
                            <?php foreach ($time_slots as $time): ?>
                                <option value="<?php echo htmlspecialchars($time); ?>"><?php echo htmlspecialchars(substr($time, 0, 5)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="booking_date">Date</label>
                        <input type="date" id="booking_date" name="booking_date" required>
                        <div id="dateError" class="error-message" style="display: none;"></div>
                    </div>

                    <div class="form-group">
                        <label for="symptoms">Symptoms</label>
                        <textarea id="symptoms" name="symptoms" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="comment">Comment</label>
                        <textarea id="comment" name="comment" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" required>
                            <option value="Scheduled" selected>Scheduled</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                            <option value="No-show">No-show</option>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="save-button" name="save_button" id="saveButton">Save</button>
                        <button type="submit" class="update-button" name="update_button" id="updateButton">Update</button>
                        <button type="submit" class="delete-button" name="delete_button" id="deleteButton">Delete</button>
                    </div>
                </div>
            </form>

            <!-- Search Form -->
            <div class="patient-list-header">
                <form method="GET" action="">
                    <input type="search" name="search" placeholder="Search appointments by patient, service or date..."
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit">Search</button>
                </form>
            </div>

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
                            <th>ADMIN</th>
                            <th>ACTIONS</th>
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
                                <td><?php echo htmlspecialchars($appointment['status']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['admin_name']); ?></td>
                                <td>
                                    <a href="#" onclick="fillForm(
                                        '<?php echo htmlspecialchars($appointment['appointment_id']); ?>',
                                        '<?php echo htmlspecialchars($appointment['patient_id']); ?>',
                                        '<?php echo htmlspecialchars($appointment['service_type_id']); ?>',
                                        '<?php echo htmlspecialchars($appointment['booking_time']); ?>',
                                        '<?php echo htmlspecialchars($appointment['booking_date']); ?>',
                                        '<?php echo htmlspecialchars($appointment['symptoms']); ?>',
                                        '<?php echo htmlspecialchars($appointment['comment']); ?>',
                                        '<?php echo htmlspecialchars($appointment['status']); ?>',
                                        '<?php echo htmlspecialchars($appointment['admin_id']); ?>',
                                        '<?php echo htmlspecialchars($appointment['admin_name']); ?>'
                                    )">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                <?php endif; ?>
            </table>
        </main>
    </div>

    <script>
        // Custom dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            const patientSearch = document.getElementById('patient_search');
            const patientOptions = document.getElementById('patient_options');
            const hiddenSelect = document.getElementById('patient_id');
            const dropdownOptions = patientOptions.querySelectorAll('.dropdown-option');

            // Show options when input is focused or clicked
            patientSearch.addEventListener('focus', function() {
                // Show all options when focused
                dropdownOptions.forEach(option => {
                    option.style.display = 'block';
                });
                patientOptions.classList.add('show');
            });

            // Filter options based on search input
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

                // Keep dropdown open if there are visible options
                if (hasVisibleOptions || searchTerm.length === 0) {
                    patientOptions.classList.add('show');
                } else {
                    patientOptions.classList.remove('show');
                }
            }

            // Handle typing in search field
            patientSearch.addEventListener('input', function() {
                filterOptions();

                // If the input is empty, show all options
                if (this.value === '') {
                    dropdownOptions.forEach(option => {
                        option.style.display = 'block';
                    });
                    patientOptions.classList.add('show');
                }
            });

            // Select option from dropdown
            dropdownOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const value = this.getAttribute('data-value');
                    const text = this.textContent;

                    patientSearch.value = text;
                    hiddenSelect.value = value;
                    patientOptions.classList.remove('show');

                    // Highlight selected option
                    dropdownOptions.forEach(opt => {
                        opt.classList.remove('selected');
                    });
                    this.classList.add('selected');
                });
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.custom-dropdown')) {
                    patientOptions.classList.remove('show');
                }
            });

            // Initialize with any existing value
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


        // Update fillForm to set the search input value
        function fillForm(appointmentId, patientId, serviceTypeId, bookingTime, bookingDate, symptoms, comment, status, adminId, adminName) {
            document.getElementById('appointmentID').value = appointmentId;
            document.getElementById('patient_id').value = patientId;
            document.getElementById('service_type_id').value = serviceTypeId;
            document.getElementById('booking_time').value = bookingTime;
            document.getElementById('booking_date').value = bookingDate;
            document.getElementById('symptoms').value = symptoms || '';
            document.getElementById('comment').value = comment || '';
            document.getElementById('status').value = status;

            // Set the patient search input value
            const selectedOption = document.querySelector(`#patient_id option[value="${patientId}"]`);
            if (selectedOption) {
                document.getElementById('patient_search').value = selectedOption.textContent;
            }

            // Set admin field (readonly)
            document.getElementById('admin_id').value = adminId + ' - ' + adminName;

            // Disable save button, enable update and delete
            document.getElementById('saveButton').disabled = true;
            document.getElementById('updateButton').disabled = false;
            document.getElementById('deleteButton').disabled = false;
        }

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

            // Reset admin field to current admin
            document.getElementById('admin_id').value = '<?php echo htmlspecialchars($admin_id . ' - ' . $admin_name); ?>';

            // Enable save button, disable update and delete
            document.getElementById('saveButton').disabled = false;
            document.getElementById('updateButton').disabled = true;
            document.getElementById('deleteButton').disabled = true;

            // Focus on patient search and show dropdown
            const patientSearch = document.getElementById('patient_search');
            const patientOptions = document.getElementById('patient_options');

            // Clear any previous selections and show all options
            const dropdownOptions = patientOptions.querySelectorAll('.dropdown-option');
            dropdownOptions.forEach(option => {
                option.style.display = 'block';
                option.classList.remove('selected');
            });

            // Show dropdown
            patientOptions.classList.add('show');

            // Focus on the input field after a slight delay to ensure dropdown is visible
            setTimeout(() => {
                patientSearch.focus();
            }, 10);
        }

        // Initialize form state when page loads
        document.addEventListener('DOMContentLoaded', function() {

            // Initially disable update and delete buttons
            document.getElementById('updateButton').disabled = true;
            document.getElementById('deleteButton').disabled = true;

            // If there's an appointment ID in the form (from form submission error), 
            // disable save and enable update/delete
            if (document.getElementById('appointmentID').value) {
                document.getElementById('saveButton').disabled = true;
                document.getElementById('updateButton').disabled = false;
                document.getElementById('deleteButton').disabled = false;
            }

            // Set min date to today
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0');
            const dd = String(today.getDate()).padStart(2, '0');
            const minDate = `${yyyy}-${mm}-${dd}`;
            document.getElementById('booking_date').setAttribute('min', minDate);
        });

        // Prevent form submission with wrong button states
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

            // Validate date is not in the past
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

        // Clear date error when date changes
        document.getElementById('booking_date').addEventListener('change', function() {
            document.getElementById('dateError').style.display = "none";
            this.classList.remove("error");
        });

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>

</html>