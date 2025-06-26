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

// Function to generate next eyes check ID
function generateEyesCheckID($conn)
{
    $sql = "SELECT MAX(eyes_check_id) AS max_id FROM eyes_check";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    // If no eyes check exists, start with EC0001
    if (empty($row['max_id'])) {
        return 'EC001';
    }

    // Extract the numeric part and increment
    $lastID = $row['max_id'];
    $numPart = intval(substr($lastID, 2));
    $newNumPart = $numPart + 1;

    // Format the new ID with leading zeros
    return 'EC' . str_pad($newNumPart, 3, '0', STR_PAD_LEFT);
}

// Validation checks
$errors = '';
$message = '';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
    $eyes_type = mysqli_real_escape_string($conn, $_POST['eyes_type']);
    $left_eye = mysqli_real_escape_string($conn, $_POST['left_eye']);
    $right_eye = mysqli_real_escape_string($conn, $_POST['right_eye']);
    $staff_id = mysqli_real_escape_string($conn, $_POST['staff_id']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);

    // Action based on button click
    if (isset($_POST['save_button'])) {
        // Validate patient exists
        $patient_check = "SELECT * FROM patient WHERE patient_id = '$patient_id'";
        $patient_result = mysqli_query($conn, $patient_check);
        if (mysqli_num_rows($patient_result) == 0) {
            $errors = "Patient ID does not exist.";
        }

        // Validate staff exists (assuming you have a staff table)
        $staff_check = "SELECT * FROM staff WHERE staff_id = '$staff_id'";
        $staff_result = mysqli_query($conn, $staff_check);
        if (mysqli_num_rows($staff_result) == 0) {
            $errors = "Staff ID does not exist.";
        }

        if (empty($errors)) {
            // Generate new eyes check ID
            $eyes_check_id = generateEyesCheckID($conn);

            // Prepare INSERT query
            $insert_query = "INSERT INTO eyes_check (eyes_check_id, patient_id, eyes_type, left_eye, right_eye, staff_id, date) 
                         VALUES ('$eyes_check_id', '$patient_id', '$eyes_type', '$left_eye', '$right_eye', '$staff_id', '$date')";

            if (mysqli_query($conn, $insert_query)) {
                $message = "Eyes check record added successfully!";
                $eyes_check_id = $patient_id = $eyes_type = $left_eye = $right_eye = $staff_id = $date = '';
            } else {
                $errors = "Error adding eyes check record: " . mysqli_error($conn);
            }
        }
    }

    // Update functionality
    if (isset($_POST['update_button']) && !empty($_POST['eyes_check_id'])) {
        $eyes_check_id = mysqli_real_escape_string($conn, $_POST['eyes_check_id']);

        // Validate patient exists
        $patient_check = "SELECT * FROM patient WHERE patient_id = '$patient_id'";
        $patient_result = mysqli_query($conn, $patient_check);
        if (mysqli_num_rows($patient_result) == 0) {
            $errors = "Patient ID does not exist.";
        }

        // Validate staff exists
        $staff_check = "SELECT * FROM staff WHERE staff_id = '$staff_id'";
        $staff_result = mysqli_query($conn, $staff_check);
        if (mysqli_num_rows($staff_result) == 0) {
            $errors = "Staff ID does not exist.";
        }

        if (empty($errors)) {
            // Prepare UPDATE query
            $update_query = "UPDATE eyes_check 
                SET patient_id = '$patient_id', 
                    eyes_type = '$eyes_type', 
                    left_eye = '$left_eye', 
                    right_eye = '$right_eye', 
                    staff_id = '$staff_id', 
                    date = '$date' 
                WHERE eyes_check_id = '$eyes_check_id'";

            if (mysqli_query($conn, $update_query)) {
                $message = "Eyes check record updated successfully!";
                $eyes_check_id = $patient_id = $eyes_type = $left_eye = $right_eye = $staff_id = $date = '';
            } else {
                $errors = "Error updating eyes check record: " . mysqli_error($conn);
            }
        }
    }

    // Delete functionality
    if (isset($_POST['delete_button']) && !empty($_POST['eyes_check_id'])) {
        $eyes_check_id = mysqli_real_escape_string($conn, $_POST['eyes_check_id']);

        // Prepare DELETE query
        $delete_query = "DELETE FROM eyes_check WHERE eyes_check_id = '$eyes_check_id'";

        if (mysqli_query($conn, $delete_query)) {
            $message = "Eyes check record deleted successfully!";
        } else {
            $errors = "Error deleting eyes check record: " . mysqli_error($conn);
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
    $search_query = "SELECT ec.*, p.first_name, p.last_name, s.first_name 
                     FROM eyes_check ec 
                     LEFT JOIN patient p ON ec.patient_id = p.patient_id 
                     LEFT JOIN staff s ON ec.staff_id = s.staff_id 
                     WHERE ec.eyes_check_id LIKE '%$search_term%' 
                     OR ec.patient_id LIKE '%$search_term%' 
                     OR p.first_name LIKE '%$search_term%' 
                     OR p.last_name LIKE '%$search_term%'
                     OR ec.eyes_type LIKE '%$search_term%'";
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

// Fetch all eyes check records if no search is performed
if (!$is_search && empty($search_results)) {
    $all_records_query = "SELECT ec.*, p.first_name, p.last_name, s.first_name 
                          FROM eyes_check ec 
                          LEFT JOIN patient p ON ec.patient_id = p.patient_id 
                          LEFT JOIN staff s ON ec.staff_id = s.staff_id 
                          ORDER BY ec.date DESC";
    $all_records_result = mysqli_query($conn, $all_records_query);

    while ($row = mysqli_fetch_assoc($all_records_result)) {
        $search_results[] = $row;
    }
}

// Fetch patients for dropdown
$patients_query = "SELECT patient_id, first_name, last_name FROM patient ORDER BY first_name, last_name";
$patients_result = mysqli_query($conn, $patients_query);

// Fetch staff for dropdown
$staff_query = "SELECT staff_id, first_name FROM staff ORDER BY first_name";
$staff_result = mysqli_query($conn, $staff_query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eyes Check Management</title>
    <link rel="stylesheet" href="patient_management.css">
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

                <li><a href="patient_management.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        Patients</a></li>

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
                        Reception</a></li>

                <li><a href="staff_management.php">
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

                <li class="active"><a href="eyes_check_management.php">
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

                <li class="has-submenu">
                    <a href="#" onclick="toggleSubmenu(this)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="20" x2="18" y2="10"></line>
                            <line x1="12" y1="20" x2="12" y2="4"></line>
                            <line x1="6" y1="20" x2="6" y2="14"></line>
                        </svg>
                        Reports
                        <svg class="chevron" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </a>
                    <ul class="submenu">
                        <li><a href="report/patient_report.php">Patient Report</a></li>
                        <li><a href="report/staff_report.php">Staff Report</a></li>
                        <li><a href="report/income_report.php">Income Report</a></li>
                    </ul>
                </li>

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
                <h1>Eyes Check Management</h1>
                <button class="new-patient-button" name="new_record" onclick="clearForm()">+ New Eyes Check</button>
            </div>

            <?php if ($message): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($errors): ?>
                <div class="error"><?php echo $errors; ?></div>
            <?php endif; ?>

            <!-- Eyes Check Form -->
            <form method="POST" action="" id="eyesCheckForm">
                <div class="patient-form">
                    <div class="form-group">
                        <label for="eyesCheckID">Eyes Check ID</label>
                        <input type="text" id="eyesCheckID" name="eyes_check_id" readonly>
                    </div>
                    <div class="form-group">
                        <label for="patientID">Patient</label>
                        <select id="patientID" name="patient_id" required>
                            <option value="">Select Patient</option>
                            <?php while ($patient = mysqli_fetch_assoc($patients_result)): ?>
                                <option value="<?php echo $patient['patient_id']; ?>">
                                    <?php echo $patient['patient_id'] . ' - ' . $patient['first_name'] . ' ' . $patient['last_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="eyesType">Eyes Type</label>
                        <select id="eyesType" name="eyes_type" required>
                            <option value="">Select Type</option>
                            <option value="Short Sighted">Short Sighted (-)</option>
                            <option value="Long Sighted">Long Sighted (+)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="leftEye">Left Eye Grade</label>
                        <input type="text" id="leftEye" name="left_eye" placeholder="e.g., -2.25" required>
                    </div>
                    <div class="form-group">
                        <label for="rightEye">Right Eye Grade</label>
                        <input type="text" id="rightEye" name="right_eye" placeholder="e.g., -1.75" required>
                    </div>
                    <div class="form-group">
                        <label for="staffID">Staff</label>
                        <select id="staffID" name="staff_id" required>
                            <option value="">Select Staff</option>
                            <?php while ($staff = mysqli_fetch_assoc($staff_result)): ?>
                                <option value="<?php echo $staff['staff_id']; ?>">
                                    <?php echo $staff['staff_id'] . ' - ' . $staff['first_name']; ?>
                                </option>
                            <?php endwhile; ?>
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
                    <input type="search" name="search" placeholder="Search by Eyes Check ID, Patient ID, or Patient Name..."
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit">Search</button>
                </form>
            </div>

            <?php if (!empty($no_results_message)): ?>
                <div class="alert alert-info">
                    <?php echo $no_results_message; ?>
                </div>
            <?php endif; ?>

            <!-- Eyes Check Table -->
            <table class="patient-table">
                <?php if (!empty($search_results)): ?>
                    <thead>
                        <tr>
                            <th>EYES CHECK ID</th>
                            <th>PATIENT ID</th>
                            <th>PATIENT NAME</th>
                            <th>EYES TYPE</th>
                            <th>LEFT EYE</th>
                            <th>RIGHT EYE</th>
                            <th>STAFF</th>
                            <th>DATE</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($search_results as $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['eyes_check_id']); ?></td>
                                <td><?php echo htmlspecialchars($record['patient_id']); ?></td>
                                <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($record['eyes_type']); ?></td>
                                <td><?php echo htmlspecialchars($record['left_eye']); ?></td>
                                <td><?php echo htmlspecialchars($record['right_eye']); ?></td>
                                <td><?php echo htmlspecialchars($record['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($record['date']); ?></td>
                                <td>
                                    <a href="#" onclick="fillForm('<?php echo htmlspecialchars($record['eyes_check_id']); ?>', 
                                '<?php echo htmlspecialchars($record['patient_id']); ?>', 
                                '<?php echo htmlspecialchars($record['eyes_type']); ?>', 
                                '<?php echo htmlspecialchars($record['left_eye']); ?>', 
                                '<?php echo htmlspecialchars($record['right_eye']); ?>', 
                                '<?php echo htmlspecialchars($record['staff_id']); ?>', 
                                '<?php echo htmlspecialchars($record['date']); ?>')">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                <?php endif; ?>
            </table>
        </main>
    </div>

    <script>
        function fillForm(eyesCheckId, patientId, eyesType, leftEye, rightEye, staffId, date) {
            document.getElementById('eyesCheckID').value = eyesCheckId;
            document.getElementById('patientID').value = patientId;
            document.getElementById('eyesType').value = eyesType;
            document.getElementById('leftEye').value = leftEye;
            document.getElementById('rightEye').value = rightEye;
            document.getElementById('staffID').value = staffId;
            document.getElementById('date').value = date;
        }

        function clearForm() {
            document.getElementById('eyesCheckID').value = '';
            document.getElementById('patientID').selectedIndex = 0;
            document.getElementById('eyesType').selectedIndex = 0;
            document.getElementById('leftEye').value = '';
            document.getElementById('rightEye').value = '';
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
            document.getElementById('date').setAttribute('max', todayDate);
        });

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Eye grade validation
        const leftEyeInput = document.getElementById("leftEye");
        const rightEyeInput = document.getElementById("rightEye");

        function validateEyeGrade(input) {
            const value = input.value.trim();
            if (value === '') return true;

            // Allow formats like: -2.25, +1.50, 0.00, -0.75
            const pattern = /^[+-]?\d+\.?\d*$/;
            return pattern.test(value);
        }

        function handleEyeGradeInput(input) {
            if (!validateEyeGrade(input)) {
                input.setCustomValidity("Please enter a valid eye grade (e.g., -2.25, +1.50, 0.00)");
            } else {
                input.setCustomValidity("");
            }
        }

        leftEyeInput.addEventListener('input', function() {
            handleEyeGradeInput(this);
        });

        rightEyeInput.addEventListener('input', function() {
            handleEyeGradeInput(this);
        });

        // Form validation
        document.getElementById('eyesCheckForm').addEventListener('submit', function(e) {
            if (!validateEyeGrade(leftEyeInput) || !validateEyeGrade(rightEyeInput)) {
                e.preventDefault();
                alert('Please enter valid eye grades for both eyes.');
            }
        });

        function toggleSubmenu(element) {
            event.preventDefault();
            const parent = element.parentElement;
            parent.classList.toggle('active');
        }
    </script>
</body>

</html>