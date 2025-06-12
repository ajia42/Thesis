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

// Function to generate next treatment ID
function generateTreatmentID($conn)
{
    $sql = "SELECT MAX(treatment_id) AS max_id FROM treatment";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    // If no treatment exists, start with T0001
    if (empty($row['max_id'])) {
        return 'T0001';
    }

    // Extract the numeric part and increment
    $lastID = $row['max_id'];
    $numPart = intval(substr($lastID, 1));
    $newNumPart = $numPart + 1;

    // Format the new ID with leading zeros
    return 'T' . str_pad($newNumPart, 4, '0', STR_PAD_LEFT);
}

// Validation checks
$errors = '';
$message = '';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $staff_id = mysqli_real_escape_string($conn, $_POST['staff_id']);
    $detail = mysqli_real_escape_string($conn, $_POST['detail']);
    $selected_diseases = isset($_POST['diseases']) ? $_POST['diseases'] : [];

    // Action based on button click
    if (isset($_POST['save_button'])) {
        // Validate required fields
        if (empty($patient_id)) {
            $errors = "Please select a patient.";
        } elseif (empty($date)) {
            $errors = "Please select a treatment date.";
        } elseif (empty($staff_id)) {
            $errors = "Please select a staff member.";
        }

        if (empty($errors)) {
            // Generate new treatment ID
            $treatment_id = generateTreatmentID($conn);

            // Start transaction
            mysqli_begin_transaction($conn);

            try {
                // Insert into treatment table
                $insert_query = "INSERT INTO treatment (treatment_id, patient_id, date, staff_id, detail) 
                             VALUES ('$treatment_id', '$patient_id', '$date', '$staff_id', '$detail')";

                if (mysqli_query($conn, $insert_query)) {
                    // Insert selected diseases into treatment_disease table (only if diseases are selected)
                    if (!empty($selected_diseases)) {
                        foreach ($selected_diseases as $disease_id) {
                            $disease_id = mysqli_real_escape_string($conn, $disease_id);

                            // Verify disease exists before inserting
                            $verify_disease = "SELECT disease_id FROM disease WHERE disease_id = '$disease_id'";
                            $verify_result = mysqli_query($conn, $verify_disease);

                            if (mysqli_num_rows($verify_result) > 0) {
                                $disease_insert = "INSERT INTO treatment_disease (treatment_id, disease_id) 
                                                 VALUES ('$treatment_id', '$disease_id')";
                                if (!mysqli_query($conn, $disease_insert)) {
                                    throw new Exception("Error inserting disease association: " . mysqli_error($conn));
                                }
                            }
                        }
                    }

                    mysqli_commit($conn);
                    $message = "Treatment added successfully!";
                    $treatment_id = $patient_id = $date = $staff_id = $detail = '';
                    $selected_diseases = [];
                } else {
                    mysqli_rollback($conn);
                    $errors = "Error adding treatment: " . mysqli_error($conn);
                }
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $errors = "Error adding treatment: " . $e->getMessage();
            }
        }
    }

    // Update functionality
    if (isset($_POST['update_button']) && !empty($_POST['treatment_id'])) {
        $treatment_id = mysqli_real_escape_string($conn, $_POST['treatment_id']);

        // Validate required fields
        if (empty($patient_id)) {
            $errors = "Please select a patient.";
        } elseif (empty($date)) {
            $errors = "Please select a treatment date.";
        } elseif (empty($staff_id)) {
            $errors = "Please select a staff member.";
        }

        if (empty($errors)) {
            // Start transaction
            mysqli_begin_transaction($conn);

            try {
                // Update treatment table
                $update_query = "UPDATE treatment 
                SET patient_id = '$patient_id', 
                    date = '$date', 
                    staff_id = '$staff_id', 
                    detail = '$detail' 
                WHERE treatment_id = '$treatment_id'";

                if (mysqli_query($conn, $update_query)) {
                    // Delete existing disease associations
                    $delete_diseases = "DELETE FROM treatment_disease WHERE treatment_id = '$treatment_id'";
                    mysqli_query($conn, $delete_diseases);

                    // Insert new disease associations (only if diseases are selected)
                    if (!empty($selected_diseases)) {
                        foreach ($selected_diseases as $disease_id) {
                            $disease_id = mysqli_real_escape_string($conn, $disease_id);

                            // Verify disease exists before inserting
                            $verify_disease = "SELECT disease_id FROM disease WHERE disease_id = '$disease_id'";
                            $verify_result = mysqli_query($conn, $verify_disease);

                            if (mysqli_num_rows($verify_result) > 0) {
                                $disease_insert = "INSERT INTO treatment_disease (treatment_id, disease_id) 
                                                 VALUES ('$treatment_id', '$disease_id')";
                                if (!mysqli_query($conn, $disease_insert)) {
                                    throw new Exception("Error updating disease association: " . mysqli_error($conn));
                                }
                            }
                        }
                    }

                    mysqli_commit($conn);
                    $message = "Treatment updated successfully!";
                    $treatment_id = $patient_id = $date = $staff_id = $detail = '';
                    $selected_diseases = [];
                } else {
                    mysqli_rollback($conn);
                    $errors = "Error updating treatment: " . mysqli_error($conn);
                }
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $errors = "Error updating treatment: " . $e->getMessage();
            }
        }
    }

    // Delete functionality
    if (isset($_POST['delete_button']) && !empty($_POST['treatment_id'])) {
        $treatment_id = mysqli_real_escape_string($conn, $_POST['treatment_id']);

        // Start transaction
        mysqli_begin_transaction($conn);

        try {
            // Delete from treatment_disease table first (foreign key constraint)
            $delete_diseases = "DELETE FROM treatment_disease WHERE treatment_id = '$treatment_id'";
            mysqli_query($conn, $delete_diseases);

            // Delete from treatment table
            $delete_query = "DELETE FROM treatment WHERE treatment_id = '$treatment_id'";

            if (mysqli_query($conn, $delete_query)) {
                mysqli_commit($conn);
                $message = "Treatment deleted successfully!";
            } else {
                mysqli_rollback($conn);
                $errors = "Error deleting treatment: " . mysqli_error($conn);
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors = "Error deleting treatment: " . $e->getMessage();
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
    $search_query = "SELECT t.*, p.first_name, p.last_name, s.first_name as staff_first_name, s.last_name as staff_last_name
                     FROM treatment t 
                     LEFT JOIN patient p ON t.patient_id = p.patient_id
                     LEFT JOIN staff s ON t.staff_id = s.staff_id
                     WHERE t.treatment_id LIKE '%$search_term%' 
                     OR p.first_name LIKE '%$search_term%' 
                     OR p.last_name LIKE '%$search_term%' 
                     OR t.detail LIKE '%$search_term%'";
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

// Fetch all treatments only if no search is performed
if (!$is_search && empty($search_results)) {
    $all_treatments_query = "SELECT t.*, p.first_name, p.last_name, s.first_name as staff_first_name, s.last_name as staff_last_name
                            FROM treatment t 
                            LEFT JOIN patient p ON t.patient_id = p.patient_id
                            LEFT JOIN staff s ON t.staff_id = s.staff_id";
    $all_treatments_result = mysqli_query($conn, $all_treatments_query);

    while ($row = mysqli_fetch_assoc($all_treatments_result)) {
        $search_results[] = $row;
    }
}

// Fetch patients for dropdown
$patients_query = "SELECT patient_id, first_name, last_name FROM patient ORDER BY first_name";
$patients_result = mysqli_query($conn, $patients_query);

// Fetch staff for dropdown
$staff_query = "SELECT staff_id, first_name, last_name FROM staff ORDER BY first_name";
$staff_result = mysqli_query($conn, $staff_query);

// Fetch diseases for checkboxes
$diseases_query = "SELECT disease_id, disease_name FROM disease ORDER BY disease_name";
$diseases_result = mysqli_query($conn, $diseases_query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Treatment Management</title>
    <link rel="stylesheet" href="patient_management.css">
    <style>
        .diseases-group {
            grid-column: span 2;
        }

        .diseases-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            max-height: 150px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 4px;
        }

        .disease-checkbox {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .disease-checkbox input[type="checkbox"] {
            margin: 0;
        }

        .disease-checkbox label {
            margin: 0;
            font-size: 14px;
            cursor: pointer;
        }
    </style>
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

                <li class="active"><a href="treatment_management.php">
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
                <h1>Treatment Management</h1>
                <button class="new-patient-button" name="new_treatment" onclick="clearForm()">+ New Treatment</button>
            </div>

            <?php if ($message): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($errors): ?>
                <div class="error"><?php echo $errors; ?></div>
            <?php endif; ?>

            <!-- Treatment Form -->
            <form method="POST" action="" id="treatmentForm">
                <div class="patient-form">
                    <div class="form-group">
                        <label for="treatmentID">Treatment ID</label>
                        <input type="text" id="treatmentID" name="treatment_id" readonly>
                    </div>
                    <div class="form-group">
                        <label for="patientSelect">Patient</label>
                        <select id="patientSelect" name="patient_id" required>
                            <option value="">Select Patient</option>
                            <?php
                            mysqli_data_seek($patients_result, 0);
                            while ($patient = mysqli_fetch_assoc($patients_result)): ?>
                                <option value="<?php echo $patient['patient_id']; ?>">
                                    <?php echo $patient['patient_id'] . ' - ' . $patient['first_name'] . ' ' . $patient['last_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="treatmentDate">Treatment Date</label>
                        <input type="date" id="treatmentDate" name="date" required>
                    </div>
                    <div class="form-group">
                        <label for="staffSelect">Staff</label>
                        <select id="staffSelect" name="staff_id" required>
                            <option value="">Select Staff</option>
                            <?php
                            mysqli_data_seek($staff_result, 0);
                            while ($staff = mysqli_fetch_assoc($staff_result)): ?>
                                <option value="<?php echo $staff['staff_id']; ?>">
                                    <?php echo $staff['staff_id'] . ' - ' . $staff['first_name'] . ' ' . $staff['last_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="detail">Treatment Details</label>
                        <textarea id="detail" name="detail" rows="3" placeholder="Enter treatment details..."></textarea>
                    </div>
                    <div class="form-group diseases-group">
                        <label>Associated Diseases</label>
                        <div class="diseases-container">
                            <?php
                            mysqli_data_seek($diseases_result, 0);
                            while ($disease = mysqli_fetch_assoc($diseases_result)): ?>
                                <div class="disease-checkbox">
                                    <input type="checkbox"
                                        id="disease_<?php echo $disease['disease_id']; ?>"
                                        name="diseases[]"
                                        value="<?php echo $disease['disease_id']; ?>">
                                    <label for="disease_<?php echo $disease['disease_id']; ?>">
                                        <?php echo htmlspecialchars($disease['disease_name']); ?>
                                    </label>
                                </div>
                            <?php endwhile; ?>
                        </div>
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
                    <input type="search" name="search" placeholder="Search treatments by ID, patient name, or details..."
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit">Search</button>
                </form>
            </div>

            <?php if (!empty($no_results_message)): ?>
                <div class="alert alert-info">
                    <?php echo $no_results_message; ?>
                </div>
            <?php endif; ?>

            <!-- Treatment Table -->
            <table class="patient-table">
                <?php if (!empty($search_results)): ?>
                    <thead>
                        <tr>
                            <th>TREATMENT ID</th>
                            <th>PATIENT</th>
                            <th>DATE</th>
                            <th>STAFF</th>
                            <th>DETAILS</th>
                            <th>DISEASES</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($search_results as $treatment): ?>
                            <?php
                            // Get associated diseases for this treatment
                            $disease_query = "SELECT d.disease_name FROM treatment_disease td 
                                            JOIN disease d ON td.disease_id = d.disease_id 
                                            WHERE td.treatment_id = '" . $treatment['treatment_id'] . "'";
                            $disease_result = mysqli_query($conn, $disease_query);
                            $diseases = [];
                            while ($disease_row = mysqli_fetch_assoc($disease_result)) {
                                $diseases[] = $disease_row['disease_name'];
                            }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($treatment['treatment_id']); ?></td>
                                <td><?php echo htmlspecialchars($treatment['first_name'] . ' ' . $treatment['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($treatment['date']); ?></td>
                                <td><?php echo htmlspecialchars($treatment['staff_first_name'] . ' ' . $treatment['staff_last_name']); ?></td>
                                <td><?php echo htmlspecialchars(substr($treatment['detail'], 0, 50)) . (strlen($treatment['detail']) > 50 ? '...' : ''); ?></td>
                                <td><?php echo htmlspecialchars(implode(', ', $diseases)); ?></td>
                                <td>
                                    <a href="#" onclick="fillForm('<?php echo htmlspecialchars($treatment['treatment_id']); ?>', 
                                        '<?php echo htmlspecialchars($treatment['patient_id']); ?>', 
                                        '<?php echo htmlspecialchars($treatment['date']); ?>', 
                                        '<?php echo htmlspecialchars($treatment['staff_id']); ?>', 
                                        '<?php echo htmlspecialchars($treatment['detail']); ?>')">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
            </table>
        <?php endif; ?>
        </main>
    </div>

    <script>
        function fillForm(treatmentId, patientId, date, staffId, detail) {
            document.getElementById('treatmentID').value = treatmentId;
            document.getElementById('patientSelect').value = patientId;
            document.getElementById('treatmentDate').value = date;
            document.getElementById('staffSelect').value = staffId;
            document.getElementById('detail').value = detail;

            // Load associated diseases
            loadTreatmentDiseases(treatmentId);
        }

        function loadTreatmentDiseases(treatmentId) {
            // Clear all checkboxes first
            document.querySelectorAll('input[name="diseases[]"]').forEach(checkbox => {
                checkbox.checked = false;
            });

            // Fetch and check associated diseases via AJAX
            fetch('get_treatment_diseases.php?treatment_id=' + encodeURIComponent(treatmentId))
                .then(response => response.json())
                .then(diseases => {
                    diseases.forEach(diseaseId => {
                        const checkbox = document.getElementById('disease_' + diseaseId);
                        if (checkbox) {
                            checkbox.checked = true;
                        }
                    });
                })
                .catch(error => {
                    console.error('Error loading treatment diseases:', error);
                });
        }

        function clearForm() {
            document.getElementById('treatmentID').value = '';
            document.getElementById('patientSelect').selectedIndex = 0;
            document.getElementById('treatmentDate').value = '';
            document.getElementById('staffSelect').selectedIndex = 0;
            document.getElementById('detail').value = '';

            // Clear all disease checkboxes
            document.querySelectorAll('input[name="diseases[]"]').forEach(checkbox => {
                checkbox.checked = false;
            });

            document.getElementById('patientSelect').focus();
        }

        // Form validation before submission
        document.getElementById('treatmentForm').addEventListener('submit', function(e) {
            const patientSelect = document.getElementById('patientSelect');
            const dateInput = document.getElementById('treatmentDate');
            const staffSelect = document.getElementById('staffSelect');

            if (patientSelect.value === '') {
                e.preventDefault();
                alert('Please select a patient.');
                patientSelect.focus();
                return false;
            }

            if (dateInput.value === '') {
                e.preventDefault();
                alert('Please select a treatment date.');
                dateInput.focus();
                return false;
            }

            if (staffSelect.value === '') {
                e.preventDefault();
                alert('Please select a staff member.');
                staffSelect.focus();
                return false;
            }

            return true;
        });

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Set max date to today for treatment date
        document.addEventListener("DOMContentLoaded", function() {
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0');
            const dd = String(today.getDate()).padStart(2, '0');
            const maxDate = `${yyyy}-${mm}-${dd}`;
            document.getElementById('treatmentDate').setAttribute('max', maxDate);
        });
    </script>
</body>

</html>