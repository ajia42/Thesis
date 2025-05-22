<?php
// Include database configuration
include("../db_config.php");

// Function to generate next checkup ID
function generateCheckupID($conn)
{
    $sql = "SELECT MAX(checkup_id) AS max_id FROM general_checkup";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    // If no checkup exists, start with GC0001
    if (empty($row['max_id'])) {
        return 'GC001';
    }

    // Extract the numeric part and increment
    $lastID = $row['max_id'];
    $numPart = intval(substr($lastID, 2));
    $newNumPart = $numPart + 1;

    // Format the new ID with leading zeros
    return 'GC' . str_pad($newNumPart, 3, '0', STR_PAD_LEFT);
}

// Validation checks
$errors = '';
$message = '';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $checkup_id = mysqli_real_escape_string($conn, $_POST['checkup_id']);
    $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
    $weight = mysqli_real_escape_string($conn, $_POST['weight']);
    $height = mysqli_real_escape_string($conn, $_POST['height']);
    $temperature = mysqli_real_escape_string($conn, $_POST['temperature']);
    $pulse = mysqli_real_escape_string($conn, $_POST['pulse']);
    $blood_pressure = mysqli_real_escape_string($conn, $_POST['blood_pressure']);
    $remark = mysqli_real_escape_string($conn, $_POST['remark']);
    $staff_id = mysqli_real_escape_string($conn, $_POST['staff_id']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);

    // Action based on button click
    if (isset($_POST['save_button'])) {
        // Check if patient exists
        $patient_check = "SELECT * FROM patient WHERE patient_id = '$patient_id'";
        $patient_result = mysqli_query($conn, $patient_check);
        if (mysqli_num_rows($patient_result) == 0) {
            $errors = "Patient ID does not exist.";
        }

        if (empty($errors)) {
            // Generate new checkup ID
            $checkup_id = generateCheckupID($conn);

            // Prepare INSERT query
            $insert_query = "INSERT INTO general_checkup (checkup_id, patient_id, weight, height, temperature, pulse, blood_pressure, remark, staff_id, date) 
                         VALUES ('$checkup_id', '$patient_id', '$weight', '$height', '$temperature', '$pulse', '$blood_pressure', '$remark', '$staff_id', '$date')";

            if (mysqli_query($conn, $insert_query)) {
                $message = "General checkup added successfully!";
                $checkup_id = $patient_id = $weight = $height = $temperature = $pulse = $blood_pressure = $remark = $staff_id = $date = '';
            } else {
                $errors = "Error adding checkup: " . mysqli_error($conn);
            }
        }
    }

    // Update functionality
    if (isset($_POST['update_button']) && !empty($_POST['checkup_id'])) {
        $checkup_id = mysqli_real_escape_string($conn, $_POST['checkup_id']);

        // Check if patient exists
        $patient_check = "SELECT * FROM patient WHERE patient_id = '$patient_id'";
        $patient_result = mysqli_query($conn, $patient_check);
        if (mysqli_num_rows($patient_result) == 0) {
            $errors = "Patient ID does not exist.";
        }

        if (empty($errors)) {
            // Prepare UPDATE query
            $update_query = "UPDATE general_checkup 
        SET patient_id = '$patient_id', 
            weight = '$weight', 
            height = '$height', 
            temperature = '$temperature', 
            pulse = '$pulse', 
            blood_pressure = '$blood_pressure', 
            remark = '$remark', 
            staff_id = '$staff_id', 
            date = '$date' 
        WHERE checkup_id = '$checkup_id'";

            if (mysqli_query($conn, $update_query)) {
                $message = "General checkup updated successfully!";
                $checkup_id = $patient_id = $weight = $height = $temperature = $pulse = $blood_pressure = $remark = $staff_id = $date = '';
            } else {
                $errors = "Error updating checkup: " . mysqli_error($conn);
            }
        }
    }

    // Delete functionality
    if (isset($_POST['delete_button']) && !empty($_POST['checkup_id'])) {
        $checkup_id = mysqli_real_escape_string($conn, $_POST['checkup_id']);

        // Prepare DELETE query
        $delete_query = "DELETE FROM general_checkup WHERE checkup_id = '$checkup_id'";

        if (mysqli_query($conn, $delete_query)) {
            $message = "General checkup deleted successfully!";
        } else {
            $errors = "Error deleting checkup: " . mysqli_error($conn);
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
    $search_query = "SELECT gc.*, p.first_name, p.last_name 
                     FROM general_checkup gc 
                     LEFT JOIN patient p ON gc.patient_id = p.patient_id
                     WHERE gc.checkup_id LIKE '%$search_term%' 
                     OR gc.patient_id LIKE '%$search_term%' 
                     OR p.first_name LIKE '%$search_term%'
                     OR p.last_name LIKE '%$search_term%'";
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

// Fetch all checkups only if no search is performed
if (!$is_search && empty($search_results)) {
    $all_checkups_query = "SELECT gc.*, p.first_name, p.last_name 
                          FROM general_checkup gc 
                          LEFT JOIN patient p ON gc.patient_id = p.patient_id";
    $all_checkups_result = mysqli_query($conn, $all_checkups_query);

    while ($row = mysqli_fetch_assoc($all_checkups_result)) {
        $search_results[] = $row;
    }
}

// Fetch patients for dropdown
$patients_query = "SELECT patient_id, first_name, last_name FROM patient";
$patients_result = mysqli_query($conn, $patients_query);
$patients = [];
while ($row = mysqli_fetch_assoc($patients_result)) {
    $patients[] = $row;
}

// Fetch staff for dropdown
$staff_query = "SELECT staff_id, first_name, last_name FROM staff";
$staff_result = mysqli_query($conn, $staff_query);
$staff = [];
while ($row = mysqli_fetch_assoc($staff_result)) {
    $staff[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>General Checkup Management</title>
    <link rel="stylesheet" href="patient_management.css">
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

                <li><a href="appointment_management.php">
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

                <li class="active"><a href="#">
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

                <li><a href="#">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                        </svg>
                        Settings</a>
                </li>
            </ul>
        </aside>
        <main class="main-content">
            <div class="header">
                <h1>General Checkup Management</h1>
                <button class="new-patient-button" name="new_checkup" onclick="clearForm()">+ New Checkup</button>
            </div>

            <?php if ($message): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($errors): ?>
                <div class="error"><?php echo $errors; ?></div>
            <?php endif; ?>

            <!-- Checkup Form -->
            <form method="POST" action="" id="checkupForm">
                <div class="patient-form">
                    <div class="form-group">
                        <label for="checkupID">Checkup ID</label>
                        <input type="text" id="checkupID" name="checkup_id" readonly>
                    </div>
                    <div class="form-group">
                        <label for="patientID">Patient</label>
                        <select id="patientID" name="patient_id" required>
                            <option value="">Select Patient</option>
                            <?php foreach ($patients as $patient): ?>
                                <option value="<?php echo $patient['patient_id']; ?>">
                                    <?php echo $patient['patient_id'] . ' - ' . $patient['first_name'] . ' ' . $patient['last_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="weight">Weight (kg)</label>
                        <input type="number" id="weight" name="weight" step="0.1" min="0">
                    </div>
                    <div class="form-group">
                        <label for="height">Height (cm)</label>
                        <input type="number" id="height" name="height" step="0.1" min="0">
                    </div>
                    <div class="form-group">
                        <label for="temperature">Temperature (°C)</label>
                        <input type="number" id="temperature" name="temperature" step="0.1" min="30" max="45">
                    </div>
                    <div class="form-group">
                        <label for="pulse">Pulse (bpm)</label>
                        <input type="number" id="pulse" name="pulse" min="0" max="300">
                    </div>
                    <div class="form-group">
                        <label for="bloodPressure">Blood Pressure</label>
                        <input type="text" id="bloodPressure" name="blood_pressure" placeholder="e.g., 120/80">
                    </div>
                    <div class="form-group">
                        <label for="remark">Remark</label>
                        <textarea id="remark" name="remark" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="staffID">Staff</label>
                        <select id="staffID" name="staff_id" required>
                            <option value="">Select Staff</option>
                            <?php foreach ($staff as $staff_member): ?>
                                <option value="<?php echo $staff_member['staff_id']; ?>">
                                    <?php echo $staff_member['staff_id'] . ' - ' . $staff_member['first_name'] . ' ' . $staff_member['last_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" id="date" name="date" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="save-button" name="save_button">Save</button>
                        <button type="submit" class="update-button" name="update_button">Update</button>
                        <button type="submit" class="delete-button" name="delete_button">Delete</button>
                    </div>
                </div>
            </form>

            <!-- Search Form -->
            <div class="patient-list-header">
                <form method="GET" action="">
                    <input type="search" name="search" placeholder="Search checkups by ID, patient name..."
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit">Search</button>
                </form>
            </div>

            <?php if (!empty($no_results_message)): ?>
                <div class="alert alert-info">
                    <?php echo $no_results_message; ?>
                </div>
            <?php endif; ?>

            <!-- Checkup Table -->
            <table class="patient-table">
                <?php if (!empty($search_results)): ?>
                    <thead>
                        <tr>
                            <th>CHECKUP ID</th>
                            <th>PATIENT</th>
                            <th>WEIGHT</th>
                            <th>HEIGHT</th>
                            <th>TEMPERATURE</th>
                            <th>PULSE</th>
                            <th>BLOOD PRESSURE</th>
                            <th>REMARK</th>
                            <th>STAFF ID</th>
                            <th>DATE</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($search_results as $checkup): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($checkup['checkup_id']); ?></td>
                                <td><?php echo htmlspecialchars($checkup['patient_id'] . ' - ' . ($checkup['first_name'] ?? '') . ' ' . ($checkup['last_name'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($checkup['weight']); ?></td>
                                <td><?php echo htmlspecialchars($checkup['height']); ?></td>
                                <td><?php echo htmlspecialchars($checkup['temperature']); ?></td>
                                <td><?php echo htmlspecialchars($checkup['pulse']); ?></td>
                                <td><?php echo htmlspecialchars($checkup['blood_pressure']); ?></td>
                                <td><?php echo htmlspecialchars($checkup['remark']); ?></td>
                                <td><?php echo htmlspecialchars($checkup['staff_id']); ?></td>
                                <td><?php echo htmlspecialchars($checkup['date']); ?></td>
                                <td>
                                    <a href="#" onclick="fillForm('<?php echo htmlspecialchars($checkup['checkup_id']); ?>', 
                                '<?php echo htmlspecialchars($checkup['patient_id']); ?>', 
                                '<?php echo htmlspecialchars($checkup['weight']); ?>', 
                                '<?php echo htmlspecialchars($checkup['height']); ?>', 
                                '<?php echo htmlspecialchars($checkup['temperature']); ?>', 
                                '<?php echo htmlspecialchars($checkup['pulse']); ?>', 
                                '<?php echo htmlspecialchars($checkup['blood_pressure']); ?>', 
                                '<?php echo htmlspecialchars($checkup['remark']); ?>', 
                                '<?php echo htmlspecialchars($checkup['staff_id']); ?>', 
                                '<?php echo htmlspecialchars($checkup['date']); ?>')">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
            </table>
        <?php endif; ?>
        </main>
    </div>

    <script>
        function fillForm(checkupId, patientId, weight, height, temperature, pulse, bloodPressure, remark, staffId, date) {
            document.getElementById('checkupID').value = checkupId;
            document.getElementById('patientID').value = patientId;
            document.getElementById('weight').value = weight;
            document.getElementById('height').value = height;
            document.getElementById('temperature').value = temperature;
            document.getElementById('pulse').value = pulse;
            document.getElementById('bloodPressure').value = bloodPressure;
            document.getElementById('remark').value = remark;
            document.getElementById('staffID').value = staffId;
            document.getElementById('date').value = date;
        }

        function clearForm() {
            document.getElementById('checkupID').value = '';
            document.getElementById('patientID').selectedIndex = 0;
            document.getElementById('weight').value = '';
            document.getElementById('height').value = '';
            document.getElementById('temperature').value = '';
            document.getElementById('pulse').value = '';
            document.getElementById('bloodPressure').value = '';
            document.getElementById('remark').value = '';
            document.getElementById('staffID').selectedIndex = 0;
            document.getElementById('date').value = '';
            document.getElementById('patientID').focus();
        }

        // Set today's date as default
        document.addEventListener("DOMContentLoaded", function() {
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0');
            const dd = String(today.getDate()).padStart(2, '0');
            const todayDate = `${yyyy}-${mm}-${dd}`;
            document.getElementById('date').value = todayDate;
        });

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>

</html>