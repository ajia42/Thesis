<?php
// Include database configuration
include("../db_config.php");

// Function to generate next appointment ID
function generateAppointmentID($conn)
{
    $sql = "SELECT MAX(appointment_id) AS max_id FROM appointment";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    // If no appointment exists, start with APP0001
    if (empty($row['max_id'])) {
        return 'AP001';
    }

    // Extract the numeric part and increment
    $lastID = $row['max_id'];
    $numPart = intval(substr($lastID, 2));
    $newNumPart = $numPart + 1;

    // Format the new ID with leading zeros
    return 'AP' . str_pad($newNumPart, 3, '0', STR_PAD_LEFT);
}

// Validation checks
$errors = '';
$message = '';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $appointment_id = isset($_POST['appointment_id']) ? mysqli_real_escape_string($conn, $_POST['appointment_id']) : '';
    $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
    $staff_id = mysqli_real_escape_string($conn, $_POST['staff_id']);
    $service_type_id = mysqli_real_escape_string($conn, $_POST['service_type_id']);
    $booking_time = mysqli_real_escape_string($conn, $_POST['booking_time']);
    $booking_date = mysqli_real_escape_string($conn, $_POST['booking_date']);
    $symptoms = mysqli_real_escape_string($conn, $_POST['symptoms']);
    $comment = mysqli_real_escape_string($conn, $_POST['comment']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Action based on button click
    if (isset($_POST['save_button'])) {
        // Check if appointment time is available
        $time_check = "SELECT * FROM appointment WHERE booking_date = '$booking_date' AND booking_time = '$booking_time'";
        $time_result = mysqli_query($conn, $time_check);
        if (mysqli_num_rows($time_result) > 0) {
            $errors = "This time slot is already booked for the selected staff.";
        }

        if (empty($errors)) {
            // Generate new appointment ID
            $appointment_id = generateAppointmentID($conn);

            // Prepare INSERT query
            $insert_query = "INSERT INTO appointment (appointment_id, patient_id, staff_id, service_type_id, booking_time, booking_date, symptoms, comment, status) 
                         VALUES ('$appointment_id', '$patient_id', '$staff_id', '$service_type_id', '$booking_time', '$booking_date', '$symptoms', '$comment', '$status')";

            if (mysqli_query($conn, $insert_query)) {
                $message = "Appointment added successfully!";
                // Clear form fields
                $appointment_id = $patient_id = $staff_id = $service_type_id = $booking_time = $booking_date = $symptoms = $comment = '';
                $status = 'pending';
            } else {
                $errors = "Error adding appointment: " . mysqli_error($conn);
            }
        }
    }

    // Update functionality
    if (isset($_POST['update_button']) && !empty($_POST['appointment_id'])) {
        $appointment_id = mysqli_real_escape_string($conn, $_POST['appointment_id']);

        // Check if the new time is available (excluding the current appointment)
        $time_check = "SELECT * FROM appointment WHERE booking_date = '$booking_date' AND booking_time = '$booking_time' AND staff_id = '$staff_id' AND appointment_id != '$appointment_id'";
        $time_result = mysqli_query($conn, $time_check);
        if (mysqli_num_rows($time_result) > 0) {
            $errors = "This time slot is already booked for the selected staff.";
        }

        if (empty($errors)) {
            // Prepare UPDATE query
            $update_query = "UPDATE appointment 
                SET patient_id = '$patient_id', 
                    staff_id = '$staff_id', 
                    service_type_id = '$service_type_id', 
                    booking_time = '$booking_time', 
                    booking_date = '$booking_date', 
                    symptoms = '$symptoms', 
                    comment = '$comment', 
                    status = '$status' 
                WHERE appointment_id = '$appointment_id'";

            if (mysqli_query($conn, $update_query)) {
                $message = "Appointment updated successfully!";
                $appointment_id = $patient_id = $staff_id = $service_type_id = $booking_time = $booking_date = $symptoms = $comment = '';
                $status = 'pending';
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

// Search functionality
$search_query = "";
$search_results = [];
$no_results_message = "";
$is_search = false;

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $is_search = true;
    $search_term = mysqli_real_escape_string($conn, $_GET['search']);
    $search_query = "SELECT a.*, p.first_name, p.last_name, s.first_name AS staff_fname, s.last_name AS staff_lname, st.service_name 
                     FROM appointment a
                     LEFT JOIN patient p ON a.patient_id = p.patient_id
                     LEFT JOIN staff s ON a.staff_id = s.staff_id
                     LEFT JOIN service_type st ON a.service_type_id = st.service_type_id
                     WHERE a.appointment_id LIKE '%$search_term%' 
                     OR p.first_name LIKE '%$search_term%' 
                     OR p.last_name LIKE '%$search_term%'
                     OR s.first_name LIKE '%$search_term%'
                     OR s.last_name LIKE '%$search_term%'
                     OR st.service_name LIKE '%$search_term%'
                     OR a.status LIKE '%$search_term%'";
    $search_result = mysqli_query($conn, $search_query);

    if ($search_result) {
        while ($row = mysqli_fetch_assoc($search_result)) {
            $search_results[] = $row;
        }

        // Display message if no records found for the search
        if (empty($search_results)) {
            $no_results_message = "No results found for: '" . htmlspecialchars($_GET['search']) . "'";
        }
    }
}

// Fetch all appointments if no search is performed
if (!$is_search && empty($search_results)) {
    $all_appointments_query = "SELECT a.*, p.first_name, p.last_name, s.first_name AS staff_fname, s.last_name AS staff_lname, st.service_name 
                              FROM appointment a
                              LEFT JOIN patient p ON a.patient_id = p.patient_id
                              LEFT JOIN staff s ON a.staff_id = s.staff_id
                              LEFT JOIN service_type st ON a.service_type_id = st.service_type_id
                              ORDER BY a.booking_date DESC, a.booking_time DESC";
    $all_appointments_result = mysqli_query($conn, $all_appointments_query);

    if ($all_appointments_result) {
        while ($row = mysqli_fetch_assoc($all_appointments_result)) {
            $search_results[] = $row;
        }
    }
}

// Fetch all patients for dropdown
$patients_query = "SELECT patient_id, first_name, last_name FROM patient ORDER BY first_name, last_name";
$patients_result = mysqli_query($conn, $patients_query);
$patients = [];
while ($row = mysqli_fetch_assoc($patients_result)) {
    $patients[] = $row;
}

// Fetch all staff for dropdown
$staff_query = "SELECT staff_id, first_name, last_name FROM staff ORDER BY first_name, last_name";
$staff_result = mysqli_query($conn, $staff_query);
$staff = [];
while ($row = mysqli_fetch_assoc($staff_result)) {
    $staff[] = $row;
}

// Fetch all service types for dropdown
$service_types_query = "SELECT service_type_id, service_name FROM service_type ORDER BY service_name";
$service_types_result = mysqli_query($conn, $service_types_query);
$service_types = [];
while ($row = mysqli_fetch_assoc($service_types_result)) {
    $service_types[] = $row;
}

// Get current date and time
$current_date = date('Y-m-d');
$current_time = date('H:i:s');

// Available time slots
$time_slots = ['08:00:00', '09:00:00', '10:00:00', '13:00:00', '14:00:00', '15:00:00'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Management</title>
    <link rel="stylesheet" href="appointment_management.css">

</head>

<body>
    <div class="container">
        <aside class="sidebar">
            <!-- Sidebar content remains the same as in the original HTML -->
            <!-- ... (previous sidebar code) ... -->
            <div class="logo">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
                Vision Care
            </div>
            <ul class="menu">

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

                <li><a href="#">
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

                <li><a href="#">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                        </svg>
                        Eyes Check</a>

                <li><a href="#">
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
                </li>
            </ul>
        </aside>
        <main class="main-content">
            <div class="header">
                <h1>Appointment Management</h1>
                <button class="new-appointment-button" onclick="clearForm()">+ New Appointment</button>
            </div>

            <?php if ($message): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($errors): ?>
                <div class="error"><?php echo $errors; ?></div>
            <?php endif; ?>

            <!-- Appointment Form -->
            <form method="POST" action="" id="appointmentForm">
                <div class="appointment-form">
                    <div class="form-group">
                        <label for="appointmentID">Appointment ID</label>
                        <input type="text" id="appointmentID" name="appointment_id" readonly>
                    </div>
                    <div class="form-group">
                        <label for="patientID">Patient</label>
                        <select id="patientID" name="patient_id" required>
                            <option value="">Select Patient</option>
                            <?php foreach ($patients as $patient): ?>
                                <option value="<?php echo $patient['patient_id']; ?>">
                                    <?php echo $patient['first_name'] . ' ' . $patient['last_name'] . ' (' . $patient['patient_id'] . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="staffID">Staff</label>
                        <select id="staffID" name="staff_id" required>
                            <option value="">Select Staff</option>
                            <?php foreach ($staff as $staff_member): ?>
                                <option value="<?php echo $staff_member['staff_id']; ?>">
                                    <?php echo $staff_member['first_name'] . ' ' . $staff_member['last_name'] . ' (' . $staff_member['staff_id'] . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="serviceTypeID">Service Type</label>
                        <select id="serviceTypeID" name="service_type_id" required>
                            <option value="">Select Service</option>
                            <?php foreach ($service_types as $service): ?>
                                <option value="<?php echo $service['service_type_id']; ?>">
                                    <?php echo $service['service_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="bookingDate">Booking Date</label>
                        <input type="date" id="bookingDate" name="booking_date" min="<?php echo $current_date; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="bookingTime">Booking Time</label>
                        <select id="bookingTime" name="booking_time" required>
                            <option value="">Select Time</option>
                            <?php foreach ($time_slots as $slot):
                                $disabled = ($booking_date == $current_date && $slot < $current_time) ? 'disabled' : '';
                                $class = ($booking_date == $current_date && $slot < $current_time) ? 'class="time-slot-disabled"' : '';
                            ?>
                                <option value="<?php echo $slot; ?>" <?php echo $disabled; ?> <?php echo $class; ?>>
                                    <?php echo date('h:i A', strtotime($slot)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="symptoms">Symptoms</label>
                        <textarea id="symptoms" name="symptoms" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="comment">Staff Comment</label>
                        <textarea id="comment" name="comment" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" required>
                            <option value="pending">Pending</option>
                            <option value="accepted">Accepted</option>
                            <option value="canceled">Canceled</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="save-button" name="save_button">Save</button>
                        <button type="submit" class="update-button" name="update_button">Update</button>
                        <button type="submit" class="delete-button" name="delete_button">Delete</button>
                    </div>
                </div>
            </form>

            <!-- Search Form -->
            <div class="appointment-list-header">
                <form method="GET" action="">
                    <input type="search" name="search" placeholder="Search appointments..."
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
            <table class="appointment-table">
                <?php if (!empty($search_results)): ?>
                    <thead>
                        <tr>
                            <th>APPOINTMENT ID</th>
                            <th>PATIENT</th>
                            <th>STAFF</th>
                            <th>SERVICE</th>
                            <th>DATE</th>
                            <th>TIME</th>
                            <th>SYMPTOMS</th>
                            <th>COMMENT</th>
                            <th>STATUS</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($search_results as $appointment):
                            $status_class = "status-" . $appointment['status'];
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($appointment['appointment_id']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['staff_fname'] . ' ' . $appointment['staff_lname']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['service_name']); ?></td>
                                <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($appointment['booking_date']))); ?></td>
                                <td><?php echo htmlspecialchars(date('h:i A', strtotime($appointment['booking_time']))); ?></td>
                                <td><?php echo htmlspecialchars($appointment['symptoms']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['comment']); ?></td>
                                <td class="<?php echo $status_class; ?>"><?php echo htmlspecialchars(ucfirst($appointment['status'])); ?></td>
                                <td>
                                    <a href="#" onclick="fillForm('<?php echo htmlspecialchars($appointment['appointment_id']); ?>', 
                                '<?php echo htmlspecialchars($appointment['patient_id']); ?>', 
                                '<?php echo htmlspecialchars($appointment['staff_id']); ?>', 
                                '<?php echo htmlspecialchars($appointment['service_type_id']); ?>', 
                                '<?php echo htmlspecialchars($appointment['booking_time']); ?>', 
                                '<?php echo htmlspecialchars($appointment['booking_date']); ?>', 
                                '<?php echo htmlspecialchars(addslashes($appointment['symptoms'])); ?>', 
                                '<?php echo htmlspecialchars(addslashes($appointment['comment'])); ?>', 
                                '<?php echo htmlspecialchars($appointment['status']); ?>')">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
            </table>
        <?php endif; ?>
        </main>
    </div>

    <script>
        // Fill form with data for editing
        function fillForm(appointmentId, patientId, staffId, serviceTypeId, bookingTime, bookingDate, symptoms, comment, status) {
            document.getElementById('appointmentID').value = appointmentId;
            document.getElementById('patientID').value = patientId;
            document.getElementById('staffID').value = staffId;
            document.getElementById('serviceTypeID').value = serviceTypeId;
            document.getElementById('bookingTime').value = bookingTime;
            document.getElementById('bookingDate').value = bookingDate;
            document.getElementById('symptoms').value = symptoms;
            document.getElementById('comment').value = comment;
            document.getElementById('status').value = status;
        }

        function clearForm() {
            document.getElementById('appointmentID').value = "";
            document.getElementById('patientID').value = "";
            document.getElementById('staffID').value = "";
            document.getElementById('serviceTypeID').value = "";
            document.getElementById('bookingTime').value = "";
            document.getElementById('bookingDate').value = "";
            document.getElementById('symptoms').value = "";
            document.getElementById('comment').value = "";
            document.getElementById('status').value = "";
            // Focus on first name input
            document.getElementById('patientID').focus();
        }
    </script>