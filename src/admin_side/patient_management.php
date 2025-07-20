<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: signin_admin.php');
    exit();
}

// Include database configuration
include("../db_config.php");

// Function to generate next patient ID
function generatePatientID($conn)
{
    $sql = "SELECT MAX(patient_id) AS max_id FROM patient";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    // If no patient exists, start with P0001
    if (empty($row['max_id'])) {
        return 'P0001';
    }

    // Extract the numeric part and increment
    $lastID = $row['max_id'];
    $numPart = intval(substr($lastID, 1));
    $newNumPart = $numPart + 1;

    // Format the new ID with leading zeros
    return 'P' . str_pad($newNumPart, 4, '0', STR_PAD_LEFT);
}

// Validation checks
$errors = '';
$message = '';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $first_name = preg_match('/^[A-Za-z\x{0E80}-\x{0EFF}\s]+$/u', $_POST['first_name']) ? mysqli_real_escape_string($conn, $_POST['first_name']) : '';
    $last_name = preg_match('/^[A-Za-z\x{0E80}-\x{0EFF}\s]+$/u', $_POST['last_name']) ? mysqli_real_escape_string($conn, $_POST['last_name']) : '';
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $dob = mysqli_real_escape_string($conn, $_POST['dob']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    // Action based on button click
    if (isset($_POST['save_button'])) {

        // Add validation checks
        if (empty($first_name)) {
            $errors = "First name must contain only letters (English or Lao)";
        }
        if (empty($last_name)) {
            $errors = "Last name must contain only letters (English or Lao)";
        }

        // Check if phone exists in patient table
        $phone_check = "SELECT * FROM patient WHERE phone = '$phone'";
        $phone_result = mysqli_query($conn, $phone_check);
        if (mysqli_num_rows($phone_result) > 0) {
            $errors = "Phone number already exists in patient records.";
        }

        if (empty($errors)) {
            // Start transaction
            mysqli_begin_transaction($conn);

            try {
                // Check if phone exists in user table
                $user_check = "SELECT * FROM user WHERE phone = '$phone'";
                $user_result = mysqli_query($conn, $user_check);

                // If phone doesn't exist in user table, insert it
                if (mysqli_num_rows($user_result) == 0) {
                    $insert_user = "INSERT INTO user (phone) VALUES ('$phone')";
                    if (!mysqli_query($conn, $insert_user)) {
                        throw new Exception("Error adding phone to user table: " . mysqli_error($conn));
                    }
                }

                // Generate new patient ID
                $patient_id = generatePatientID($conn);

                // Prepare INSERT query for patient
                $insert_patient = "INSERT INTO patient (patient_id, first_name, last_name, gender, phone, dob, address) 
                                 VALUES ('$patient_id', '$first_name', '$last_name', '$gender', '$phone', '$dob', '$address')";

                if (mysqli_query($conn, $insert_patient)) {
                    mysqli_commit($conn);
                    $message = "Patient added successfully!";
                    $patient_id = $first_name = $last_name = $gender = $phone = $dob = $address = '';
                } else {
                    throw new Exception("Error adding patient: " . mysqli_error($conn));
                }
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $errors = $e->getMessage();
            }
        }
    }

    // Handle update functionality
    if (isset($_POST['update_button']) && !empty($_POST['patient_id'])) {
        $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
        $first_name = preg_match('/^[A-Za-z\x{0E80}-\x{0EFF}\s]+$/u', $_POST['first_name']) ? mysqli_real_escape_string($conn, $_POST['first_name']) : '';
        $last_name = preg_match('/^[A-Za-z\x{0E80}-\x{0EFF}\s]+$/u', $_POST['last_name']) ? mysqli_real_escape_string($conn, $_POST['last_name']) : '';
        $gender = mysqli_real_escape_string($conn, $_POST['gender']);
        $new_phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $original_phone = mysqli_real_escape_string($conn, $_POST['original_phone']);
        $dob = mysqli_real_escape_string($conn, $_POST['dob']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);

        if (empty($first_name)) {
            $errors = "First name must contain only letters (English or Lao)";
        }
        if (empty($last_name)) {
            $errors = "Last name must contain only letters (English or Lao)";
        }

        // Validate phone number
        if (!preg_match('/^20\d{8}$/', $new_phone)) {
            $errors = "Phone must start with 20 and be 10 digits (e.g., 2012345678)";
        } else {
            // Start transaction
            mysqli_begin_transaction($conn);

            try {
                // Check if phone number is being changed
                if ($new_phone != $original_phone) {
                    // Check if new phone exists in user table (excluding current record)
                    $user_check = "SELECT * FROM user WHERE phone = '$new_phone' AND phone != '$original_phone'";
                    $user_result = mysqli_query($conn, $user_check);

                    if (mysqli_num_rows($user_result) > 0) {
                        throw new Exception("Phone number already registered in user records");
                    }

                    // Check if new phone exists in patient table (excluding current record)
                    $patient_check = "SELECT * FROM patient WHERE phone = '$new_phone' AND phone != '$original_phone'";
                    $patient_result = mysqli_query($conn, $patient_check);

                    if (mysqli_num_rows($patient_result) > 0) {
                        throw new Exception("Phone number already registered in patient records");
                    }

                    // Update user table first (if exists)
                    $update_user = "UPDATE user SET phone = '$new_phone' WHERE phone = '$original_phone'";
                    if (!mysqli_query($conn, $update_user)) {
                        throw new Exception("Error updating user phone: " . mysqli_error($conn));
                    }
                }

                // Update patient table
                $update_patient = "UPDATE patient SET 
                              first_name = '$first_name', 
                              last_name = '$last_name', 
                              gender = '$gender', 
                              phone = '$new_phone', 
                              dob = '$dob', 
                              address = '$address' 
                              WHERE patient_id = '$patient_id'";

                if (!mysqli_query($conn, $update_patient)) {
                    throw new Exception("Error updating patient: " . mysqli_error($conn));
                }

                // If we got here, all queries were successful - commit transaction
                mysqli_commit($conn);
                $message = "Patient updated successfully!";

                // Clear the form
                $patient_id = $first_name = $last_name = $gender = $new_phone = $dob = $address = '';
            } catch (Exception $e) {
                // Something went wrong - rollback transaction
                mysqli_rollback($conn);
                $errors = $e->getMessage();
            }
        }
    }

    // Delete functionality
    if (isset($_POST['delete_button']) && !empty($_POST['patient_id'])) {
        $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);

        // List of all tables that reference patient with their actual column names
        $related_tables = [
            'appointment' => ['column' => 'patient_id', 'name' => 'appointments'],
            'reception' => ['column' => 'patient_id', 'name' => 'reception records'],
            'treatment' => ['column' => 'patient_id', 'name' => 'treatments'],
            'eyes_check' => ['column' => 'patient_id', 'name' => 'eye check records'],
            'general_checkup' => ['column' => 'patient_id', 'name' => 'general checkups'],
            'receipt' => ['column' => 'patient_id', 'name' => 'receipts'],
            // 'treatment_disease' => ['column' => 'patient_id', 'name' => 'treatment disease records']
        ];

        $existing_records = [];

        // Check each related table for records
        foreach ($related_tables as $table => $info) {
            try {
                $check_query = "SELECT * FROM $table WHERE {$info['column']} = '$patient_id' LIMIT 1";
                $result = mysqli_query($conn, $check_query);

                if ($result === false) {
                    // If query fails, check if the table exists
                    $table_check = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
                    if (mysqli_num_rows($table_check) > 0) {
                        // Table exists but query failed - likely wrong column name
                        throw new Exception("Error checking $table table: " . mysqli_error($conn));
                    }
                    // Table doesn't exist - skip it
                    continue;
                }

                if (mysqli_num_rows($result) > 0) {
                    $existing_records[] = $info['name'];
                }
            } catch (Exception $e) {
                // Log the error but continue checking other tables
                error_log("Error checking $table table: " . $e->getMessage());
                $existing_records[] = $info['name'] . " (check failed)";
            }
        }

        // If any related records exist, show error
        if (!empty($existing_records)) {
            $errors = "Cannot delete patient because they have existing: " .
                implode(", ", $existing_records) . ". " .
                "Please delete these records first or contact your system administrator.";
        } else {
            // Start transaction only if no related records exist
            mysqli_begin_transaction($conn);

            try {
                // First delete the patient
                $delete_patient = "DELETE FROM patient WHERE patient_id = '$patient_id'";
                if (!mysqli_query($conn, $delete_patient)) {
                    throw new Exception("Error deleting patient: " . mysqli_error($conn));
                }

                // Check if phone is used by any other patient
                $patient_check = "SELECT * FROM patient WHERE phone = '$phone'";
                $patient_result = mysqli_query($conn, $patient_check);

                // If no other patient uses this phone, remove from user table
                if (mysqli_num_rows($patient_result) == 0) {
                    $delete_user = "DELETE FROM user WHERE phone = '$phone'";
                    if (!mysqli_query($conn, $delete_user)) {
                        throw new Exception("Error removing phone from user table: " . mysqli_error($conn));
                    }
                }

                mysqli_commit($conn);
                $message = "Patient deleted successfully!";
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $errors = $e->getMessage();
            }
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
    $search_query = "SELECT * FROM patient 
                     WHERE patient_id LIKE '%$search_term%' 
                     OR first_name LIKE '%$search_term%' 
                     OR last_name LIKE '%$search_term%'
                     OR phone LIKE '%$search_term%'";
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

// Fetch all patients only if no search is performed
if (!$is_search && empty($search_results)) {
    $all_patients_query = "SELECT * FROM patient";
    $all_patients_result = mysqli_query($conn, $all_patients_query);

    while ($row = mysqli_fetch_assoc($all_patients_result)) {
        $search_results[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Management</title>
    <link rel="stylesheet" href="patient_management.css">
    <link rel="icon" href="../images/logo.svg" type="image/svg+xml">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Phetsarath:wght@400;700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="container">
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

                <li><a href="appointment_management.php">
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
                <h1>ຈັດການ ຂໍ້ມູນຄົນເຈັບ</h1>
                <button class="new-patient-button" name="new_patient" onclick="clearForm()">+ ເພີ່ມຂໍ້ມູນຄົນເຈັບ</button>
            </div>

            <?php if ($message): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($errors): ?>
                <div class="error"><?php echo $errors; ?></div>
            <?php endif; ?>

            <!-- Patient Form -->
            <form method="POST" action="" id="patientForm">
                <input type="hidden" id="original_phone" name="original_phone">
                <div class="patient-form">
                    <div class="form-group">
                        <label for="patientID">ລະຫັດຄົນເຈັບ</label>
                        <input type="text" id="patientID" name="patient_id" readonly>
                    </div>
                    <div class="form-group">
                        <label for="firstName">ຊື່</label>
                        <input
                            type="text"
                            id="firstName"
                            name="first_name"
                            pattern="[A-Za-z\u0E80-\u0EFF ]+"
                            title="Only letters are allowed (English or Lao)"
                            required>
                    </div>
                    <div class="form-group">
                        <label for="lastName">ນາມສະກຸນ</label>
                        <input
                            type="text"
                            id="lastName"
                            name="last_name"
                            pattern="[A-Za-z\u0E80-\u0EFF ]+"
                            title="Only letters are allowed (English or Lao)"
                            required>
                    </div>
                    <div class="form-group">
                        <label for="gender">ເພດ</label>
                        <select id="gender" name="gender">
                            <option value="Male" selected>ຊາຍ</option>
                            <option value="Female">ຍິງ</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="phone">ເບີໂທ</label>
                        <input
                            type="text"
                            id="phone"
                            name="phone"
                            placeholder="Enter phone number 20xxxxxxxx"
                            maxlength="10"
                            inputmode="numeric"
                            required />
                        <div id="phoneError" class="error-message" style="display: none;"></div>
                    </div>
                    <div class="form-group">
                        <label for="dob">ວັນເດືອນປີເກີດ</label>
                        <input type="date" id="dob" name="dob" required>
                        <div id="dobError" class="error-message" style="display: none;"></div>
                    </div>
                    <div class="form-group">
                        <label for="address">ທີ່ຢູ່</label>
                        <input type="text" id="address" name="address">
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="save-button" name="save_button" id="saveButton">ບັນທຶກ</button>
                        <button type="submit" class="update-button" name="update_button" id="updateButton">ແກ້ໄຂ</button>
                        <button type="submit" class="delete-button" name="delete_button" id="deleteButton">ລຶບອອກ</button>
                    </div>
                </div>
            </form>

            <!-- Search Form -->
            <div class="patient-list-header">
                <form method="GET" action="">
                    <input type="search" name="search" placeholder="Search patients by name or phone..."
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit">ຄົ້ນຫາ</button>
                </form>
            </div>

            <?php if (!empty($no_results_message)): ?>
                <div class="alert alert-info">
                    <?php echo $no_results_message; ?>
                </div>
            <?php endif; ?>

            <!-- Patient Table -->
            <table class="patient-table">
                <?php if (!empty($search_results)): ?>
                    <thead>
                        <tr>
                            <th>ລະຫັດຄົນເຈັບ</th>
                            <th>ຊື່</th>
                            <th>ນາມສະກຸນ</th>
                            <th>ເພດ</th>
                            <th>ເບີໂທ</th>
                            <th>ວັນເດືອນປີເກີດ</th>
                            <th>ທີ່ຢູ່</th>
                            <th>ສະແດງຂໍ້ມູນ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($search_results as $patient): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($patient['patient_id']); ?></td>
                                <td><?php echo htmlspecialchars($patient['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($patient['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($patient['gender']); ?></td>
                                <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                                <td><?php echo htmlspecialchars($patient['dob']); ?></td>
                                <td><?php echo htmlspecialchars($patient['address']); ?></td>
                                <td>
                                    <a href="#" onclick="fillForm('<?php echo htmlspecialchars($patient['patient_id']); ?>', 
                                '<?php echo htmlspecialchars($patient['first_name']); ?>', 
                                '<?php echo htmlspecialchars($patient['last_name']); ?>', 
                                '<?php echo htmlspecialchars($patient['gender']); ?>', 
                                '<?php echo htmlspecialchars($patient['phone']); ?>', 
                                '<?php echo htmlspecialchars($patient['dob']); ?>', 
                                '<?php echo htmlspecialchars($patient['address']); ?>')">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
            </table>
        <?php endif; ?>
        </main>
    </div>

    <script>
        function fillForm(patientId, firstName, lastName, gender, phone, dob, address) {
            document.getElementById('patientID').value = patientId;
            document.getElementById('firstName').value = firstName;
            document.getElementById('lastName').value = lastName;
            document.getElementById('gender').value = gender;
            document.getElementById('phone').value = phone;
            document.getElementById('original_phone').value = phone;
            document.getElementById('dob').value = dob;
            document.getElementById('address').value = address;

            // Disable save button, enable update and delete
            document.getElementById('saveButton').disabled = true;
            document.getElementById('updateButton').disabled = false;
            document.getElementById('deleteButton').disabled = false;

            // Scroll to form
            document.getElementById('patientForm').scrollIntoView({
                behavior: 'smooth'
            });
        }

        function clearForm() {
            document.getElementById('patientID').value = '';
            document.getElementById('firstName').value = '';
            document.getElementById('lastName').value = '';
            document.getElementById('gender').selectedIndex = 0;
            document.getElementById('phone').value = '';
            document.getElementById('original_phone').value = '';
            document.getElementById('dob').value = '';
            document.getElementById('address').value = '';
            document.getElementById('firstName').focus();

            // Enable save button, disable update and delete
            document.getElementById('saveButton').disabled = false;
            document.getElementById('updateButton').disabled = true;
            document.getElementById('deleteButton').disabled = true;
        }

        // Add this to initialize the form state when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Initially disable update and delete buttons
            document.getElementById('updateButton').disabled = true;
            document.getElementById('deleteButton').disabled = true;

            // If there's a patient ID in the form (from form submission error), 
            // we should disable save and enable update/delete
            if (document.getElementById('patientID').value) {
                document.getElementById('saveButton').disabled = true;
                document.getElementById('updateButton').disabled = false;
                document.getElementById('deleteButton').disabled = false;
            }
        });

        // Add this to prevent form submission with wrong button states
        document.getElementById('patientForm').addEventListener('submit', function(e) {
            const patientId = document.getElementById('patientID').value;
            const isSave = e.submitter.name === 'save_button';
            const isUpdate = e.submitter.name === 'update_button';
            const isDelete = e.submitter.name === 'delete_button';

            if (isSave && patientId) {
                e.preventDefault();
                alert("Error: You're trying to save an existing record. Use Update instead.");
                return;
            }

            if ((isUpdate || isDelete) && !patientId) {
                e.preventDefault();
                alert("Error: No patient selected. Please select a patient to edit first.");
                return;
            }
        });

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        const phoneInput = document.getElementById("phone");
        const phoneError = document.getElementById("phoneError");
        const patientForm = document.getElementById("patientForm");

        // Phone validation function
        function validatePhoneNumber(phone) {
            // Check if it's exactly 10 digits
            if (!/^\d{10}$/.test(phone)) {
                return {
                    valid: false,
                    message: "Phone number must be exactly 10 digits"
                };
            }

            // Check if it starts with 20
            if (!phone.startsWith('20')) {
                return {
                    valid: false,
                    message: "Phone number must start with '20'"
                };
            }

            return {
                valid: true
            };
        }

        // Show error message
        function showPhoneError(message) {
            phoneError.textContent = message;
            phoneError.style.display = "block";
            phoneInput.classList.add("error");
        }

        // Hide error message
        function hidePhoneError() {
            phoneError.style.display = "none";
            phoneInput.classList.remove("error");
        }

        // Allow only digits on keypress
        phoneInput.addEventListener("keypress", function(e) {
            if (!/^\d$/.test(e.key)) {
                e.preventDefault();
            }
        });

        // Clean pasted values (digits only) and validate on input
        phoneInput.addEventListener("input", function(e) {
            // Remove all non-digit characters
            this.value = this.value.replace(/\D/g, "");

            // Limit to 10 digits
            if (this.value.length > 10) {
                this.value = this.value.slice(0, 10);
            }

            // Clear error if field is empty
            if (this.value.length === 0) {
                hidePhoneError();
            }
        });

        // Validate when leaving the input field
        phoneInput.addEventListener("blur", function() {
            if (this.value.length > 0) {
                const validation = validatePhoneNumber(this.value);
                if (!validation.valid) {
                    showPhoneError(validation.message);
                } else {
                    hidePhoneError();
                }
            }
        });

        // Form submission validation
        patientForm.addEventListener("submit", function(e) {
            // Only validate if there's a value
            if (phoneInput.value.length > 0) {
                const validation = validatePhoneNumber(phoneInput.value);
                if (!validation.valid) {
                    e.preventDefault();
                    showPhoneError(validation.message);
                    phoneInput.focus();
                }
            }
        });

        // Date of birth validation
        const dobInput = document.getElementById("dob");
        const dobError = document.getElementById("dobError");

        // Set max date to today when the page loads
        function setMaxDate() {
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0');
            const dd = String(today.getDate()).padStart(2, '0');
            const maxDate = `${yyyy}-${mm}-${dd}`;
            dobInput.setAttribute('max', maxDate);
        }

        // Calculate age from date of birth
        function calculateAge(birthDate) {
            const today = new Date();
            const dob = new Date(birthDate);
            let age = today.getFullYear() - dob.getFullYear();
            const monthDiff = today.getMonth() - dob.getMonth();

            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                age--;
            }

            return age;
        }

        // Show DOB error message
        function showDobError(message) {
            dobError.textContent = message;
            dobError.style.display = "block";
            dobInput.classList.add("error");
        }

        // Hide DOB error message
        function hideDobError() {
            dobError.style.display = "none";
            dobInput.classList.remove("error");
        }

        // Validate date of birth
        function validateDob(dob) {
            const selectedDate = new Date(dob);
            const today = new Date();

            // Check if date is in the future
            if (selectedDate > today) {
                return {
                    valid: false,
                    message: "Date of birth cannot be in the future"
                };
            }

            // Check age range (assuming patients should be between 0 and 120 years old)
            const age = calculateAge(dob);
            if (age > 120) {
                return {
                    valid: false,
                    message: "Age cannot exceed 120 years"
                };
            }

            return {
                valid: true,
                message: ""
            };
        }

        // Set max date when page loads
        document.addEventListener("DOMContentLoaded", function() {
            setMaxDate();
        });

        // Validate DOB when input changes
        dobInput.addEventListener("change", function() {
            if (this.value) {
                const validation = validateDob(this.value);
                if (!validation.valid) {
                    showDobError(validation.message);
                } else {
                    hideDobError();
                }
            } else {
                hideDobError();
            }
        });

        // Add DOB validation to form submission
        patientForm.addEventListener("submit", function(e) {
            if (dobInput.value) {
                const validation = validateDob(dobInput.value);
                if (!validation.valid) {
                    e.preventDefault();
                    showDobError(validation.message);
                    dobInput.focus();
                }
            }
        });

        // function toggleSubmenu(element, forceOpen = false) {
        //     event.preventDefault();
        //     const parent = element.parentElement;
        //     const submenu = parent.querySelector('.submenu');

        //     if (forceOpen) {
        //         submenu.style.display = 'block';
        //         parent.classList.add('active');
        //     } else {
        //         // Toggle the visibility of the submenu
        //         submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';

        //         // Toggle active class
        //         if (submenu.style.display === 'block') {
        //             parent.classList.add('active');
        //         } else {
        //             parent.classList.remove('active');
        //         }
        //     }
        // }

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
        //     const submenu = parent.querySelector('.submenu');

        //     // Toggle the visibility of the submenu
        //     submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';

        //     // Manage active class
        //     if (submenu.style.display === 'block') {
        //         parent.classList.add('active');
        //     } else {
        //         parent.classList.remove('active');
        //     }
        // }

        // Add this to your existing script section
        function isAllowedCharacter(char) {
            // Allow English letters (A-Z, a-z), Lao characters, and space
            return /^[A-Za-z\u0E80-\u0EFF ]$/.test(char);
        }

        function validateNameInput(inputElement) {
            inputElement.addEventListener('keypress', function(e) {
                if (!isAllowedCharacter(e.key) && e.key !== 'Backspace' && e.key !== 'Delete') {
                    e.preventDefault();
                }
            });

            inputElement.addEventListener('paste', function(e) {
                e.preventDefault();
                const pasteText = (e.clipboardData || window.clipboardData).getData('text');
                const filteredText = pasteText.split('').filter(isAllowedCharacter).join('');
                document.execCommand('insertText', false, filteredText);
            });

            inputElement.addEventListener('input', function() {
                this.value = this.value.split('').filter(isAllowedCharacter).join('');
            });
        }

        // Initialize validation when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            validateNameInput(document.getElementById('firstName'));
            validateNameInput(document.getElementById('lastName'));

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
    </script>
</body>

</html>