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
    $checkup_id = isset($_POST['checkup_id']) ? mysqli_real_escape_string($conn, $_POST['checkup_id']) : '';

    // Action based on button click
    if (isset($_POST['save_button'])) {
        $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
        $weight = mysqli_real_escape_string($conn, $_POST['weight']);
        $height = mysqli_real_escape_string($conn, $_POST['height']);
        $temperature = mysqli_real_escape_string($conn, $_POST['temperature']);
        $pulse = mysqli_real_escape_string($conn, $_POST['pulse']);
        $blood_pressure = mysqli_real_escape_string($conn, $_POST['blood_pressure']);
        $remark = mysqli_real_escape_string($conn, $_POST['remark']);
        $staff_id = mysqli_real_escape_string($conn, $_POST['staff_id']);
        $date = mysqli_real_escape_string($conn, $_POST['date']);
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
        $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
        $weight = mysqli_real_escape_string($conn, $_POST['weight']);
        $height = mysqli_real_escape_string($conn, $_POST['height']);
        $temperature = mysqli_real_escape_string($conn, $_POST['temperature']);
        $pulse = mysqli_real_escape_string($conn, $_POST['pulse']);
        $blood_pressure = mysqli_real_escape_string($conn, $_POST['blood_pressure']);
        $remark = mysqli_real_escape_string($conn, $_POST['remark']);
        $staff_id = mysqli_real_escape_string($conn, $_POST['staff_id']);
        $date = mysqli_real_escape_string($conn, $_POST['date']);

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
    if (isset($_POST['delete_button'])) {
        // Only need checkup_id for delete


        if (!empty($checkup_id)) {
            // Prepare DELETE query
            $delete_query = "DELETE FROM general_checkup WHERE checkup_id = '$checkup_id'";

            if (mysqli_query($conn, $delete_query)) {
                $message = "General checkup deleted successfully!";
            } else {
                $errors = "Error deleting checkup: " . mysqli_error($conn);
            }
        } else {
            $errors = "No checkup selected for deletion";
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
    $patients[$row['patient_id']] = $row['patient_id'] . ' - ' . $row['first_name'] . ' ' . $row['last_name'];
}

// Fetch staff for dropdown (only doctors and nurses)
$staff_query = "SELECT staff_id, first_name, last_name, position FROM staff 
                WHERE position IN ('Doctor', 'Nurse') 
                ORDER BY position, first_name, last_name";
$staff_result = mysqli_query($conn, $staff_query);
$staff = [];
while ($row = mysqli_fetch_assoc($staff_result)) {
    $staff[$row['staff_id']] = $row['staff_id'] . ' - ' . $row['first_name'] . ' ' . $row['last_name'] . ' (' . $row['position'] . ')';
}

// Get current date for JavaScript
$current_date = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>General Checkup Management</title>
    <link rel="stylesheet" href="patient_management.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Phetsarath:wght@400;700&display=swap" rel="stylesheet">
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

        .print-link {
            color: #1976d2;
            text-decoration: none;
            cursor: pointer;
        }

        .print-link:hover {
            text-decoration: underline;
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

                <li class="active"><a href="checkup_management.php">
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
                        <label for="blood_pressure">Blood Pressure</label>
                        <input type="text" id="blood_pressure" name="blood_pressure" placeholder="e.g., 120/80" pattern="\d{1,3}/\d{1,3}" title="Please enter a valid blood pressure (e.g., 120/80)">
                    </div>
                    <div class="form-group">
                        <label for="remark">Remark</label>
                        <textarea id="remark" name="remark" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="staff_id">Staff</label>
                        <div class="custom-dropdown">
                            <input type="text" id="staff_search" class="dropdown-input" placeholder="Type a name..." autocomplete="off">
                            <select id="staff_id" name="staff_id" class="hidden-select" required>
                                <option value="">Select Staff</option>
                                <?php foreach ($staff as $id => $name): ?>
                                    <option value="<?php echo htmlspecialchars($id); ?>"><?php echo htmlspecialchars($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div id="staff_options" class="dropdown-options">
                                <?php foreach ($staff as $id => $name): ?>
                                    <div class="dropdown-option" data-value="<?php echo htmlspecialchars($id); ?>"><?php echo htmlspecialchars($name); ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" id="date" name="date" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="save-button" name="save_button" id="saveButton">Save</button>
                        <button type="submit" class="update-button" name="update_button" id="updateButton" disabled>Update</button>
                        <button type="submit" class="delete-button" name="delete_button" id="deleteButton" disabled>Delete</button>
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
                            <th>DATE</th>
                            <th>ACTIONS</th>
                            <th>DELETE</th>
                            <th>PRINT</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($search_results as $checkup): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($checkup['checkup_id']); ?></td>
                                <td><?php echo htmlspecialchars($checkup['first_name'] . ' ' . $checkup['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($checkup['weight']); ?></td>
                                <td><?php echo htmlspecialchars($checkup['height']); ?></td>
                                <td><?php echo htmlspecialchars($checkup['temperature']); ?></td>
                                <td><?php echo htmlspecialchars($checkup['pulse']); ?></td>
                                <td><?php echo htmlspecialchars($checkup['blood_pressure']); ?></td>
                                <td><?php echo htmlspecialchars($checkup['date']); ?></td>
                                <td>
                                    <a href="#" onclick="showEditModal(
                                        '<?php echo htmlspecialchars($checkup['checkup_id']); ?>',
                                        '<?php echo htmlspecialchars($checkup['patient_id']); ?>',
                                        '<?php echo htmlspecialchars($checkup['weight']); ?>',
                                        '<?php echo htmlspecialchars($checkup['height']); ?>',
                                        '<?php echo htmlspecialchars($checkup['temperature']); ?>',
                                        '<?php echo htmlspecialchars($checkup['pulse']); ?>',
                                        '<?php echo htmlspecialchars($checkup['blood_pressure']); ?>',
                                        '<?php echo htmlspecialchars($checkup['remark']); ?>',
                                        '<?php echo htmlspecialchars($checkup['staff_id']); ?>',
                                        '<?php echo htmlspecialchars($checkup['date']); ?>'
                                    )">Edit</a>
                                </td>
                                <td>
                                    <a href="#" class="delete-link" onclick="confirmDelete('<?php echo htmlspecialchars($checkup['checkup_id']); ?>')">Delete</a>
                                </td>
                                <td>
                                    <a href="paper/print_checkup.php?id=<?php echo htmlspecialchars($checkup['checkup_id']); ?>" target="_blank" class="print-link">Print</a>
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
            <h2>Edit Checkup</h2>
            <form id="editForm" method="POST" action="checkup_management.php">
                <input type="hidden" id="modal_checkup_id" name="checkup_id">
                <input type="hidden" id="modal_hidden_patient_id" name="patient_id">
                <div class="patient-form">
                    <div class="form-group">
                        <label for="display_checkup_id">Checkup ID</label>
                        <input type="text" id="display_checkup_id" name="display_checkup_id" readonly class="readonly-field">
                    </div>

                    <div class="form-group">
                        <label for="modal_patient_id">Patient</label>
                        <select id="modal_patient_id" name="patient_id" disabled class="readonly-field">
                            <option value="">Select Patient</option>
                            <?php foreach ($patients as $id => $name): ?>
                                <option value="<?php echo htmlspecialchars($id); ?>"><?php echo htmlspecialchars($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="modal_weight">Weight (kg)</label>
                        <input type="number" id="modal_weight" name="weight" step="0.1" min="0">
                    </div>
                    <div class="form-group">
                        <label for="modal_height">Height (cm)</label>
                        <input type="number" id="modal_height" name="height" step="0.1" min="0">
                    </div>
                    <div class="form-group">
                        <label for="modal_temperature">Temperature (°C)</label>
                        <input type="number" id="modal_temperature" name="temperature" step="0.1" min="30" max="45">
                    </div>
                    <div class="form-group">
                        <label for="modal_pulse">Pulse (bpm)</label>
                        <input type="number" id="modal_pulse" name="pulse" min="0" max="300">
                    </div>
                    <div class="form-group">
                        <label for="modal_blood_pressure">Blood Pressure</label>
                        <input type="text" id="modal_blood_pressure" name="blood_pressure" placeholder="e.g., 120/80"
                            pattern="\d{1,3}/\d{1,3}" title="Please enter a valid blood pressure (e.g., 120/80)">
                    </div>
                    <div class="form-group">
                        <label for="modal_remark">Remark</label>
                        <textarea id="modal_remark" name="remark" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="modal_staff_id">Staff</label>
                        <div class="custom-dropdown">
                            <input type="text" id="modal_staff_search" class="dropdown-input" placeholder="Type a name..." autocomplete="off">
                            <select id="modal_staff_id" name="staff_id" class="hidden-select" required>
                                <option value="">Select Staff</option>
                                <?php foreach ($staff as $id => $name): ?>
                                    <option value="<?php echo htmlspecialchars($id); ?>"><?php echo htmlspecialchars($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div id="modal_staff_options" class="dropdown-options">
                                <?php foreach ($staff as $id => $name): ?>
                                    <div class="dropdown-option" data-value="<?php echo htmlspecialchars($id); ?>"><?php echo htmlspecialchars($name); ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="modal_date">Date</label>
                        <input type="date" id="modal_date" name="date" required>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="cancel-button" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="update-button" name="update_button">Update</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content" style="width: 40%;">
            <h2>Confirm Delete</h2>
            <p>Are you sure you want to delete this checkup?</p>
            <form id="deleteForm" method="POST" action="">
                <input type="hidden" id="delete_checkup_id" name="checkup_id">
                <div class="modal-actions">
                    <button type="button" class="cancel-button" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="delete-button" name="delete_button">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Custom dropdown functionality
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

            // Set today's date as default
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0');
            const dd = String(today.getDate()).padStart(2, '0');
            const todayDate = `${yyyy}-${mm}-${dd}`;
            document.getElementById('date').value = todayDate;
            document.getElementById('modal_date')?.setAttribute('min', todayDate);

            // Add this to the existing JavaScript code
            const staffSearch = document.getElementById('staff_search');
            const staffOptions = document.getElementById('staff_options');
            const staffHiddenSelect = document.getElementById('staff_id');
            const staffDropdownOptions = staffOptions.querySelectorAll('.dropdown-option');

            staffSearch.addEventListener('focus', function() {
                staffDropdownOptions.forEach(option => {
                    option.style.display = 'block';
                });
                staffOptions.classList.add('show');
            });

            function filterStaffOptions() {
                const searchTerm = staffSearch.value.toLowerCase();
                let hasVisibleOptions = false;

                staffDropdownOptions.forEach(option => {
                    const optionText = option.textContent.toLowerCase();
                    if (optionText.includes(searchTerm)) {
                        option.style.display = 'block';
                        hasVisibleOptions = true;
                    } else {
                        option.style.display = 'none';
                    }
                });

                if (hasVisibleOptions || searchTerm.length === 0) {
                    staffOptions.classList.add('show');
                } else {
                    staffOptions.classList.remove('show');
                }
            }

            staffSearch.addEventListener('input', filterStaffOptions);

            staffDropdownOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const value = this.getAttribute('data-value');
                    const text = this.textContent;

                    staffSearch.value = text;
                    staffHiddenSelect.value = value;
                    staffOptions.classList.remove('show');

                    staffDropdownOptions.forEach(opt => {
                        opt.classList.remove('selected');
                    });
                    this.classList.add('selected');
                });
            });

            document.addEventListener('click', function(e) {
                if (!e.target.closest('.custom-dropdown')) {
                    staffOptions.classList.remove('show');
                }
            });

            if (staffHiddenSelect.value) {
                const selectedOption = staffHiddenSelect.querySelector('option:checked');
                if (selectedOption) {
                    staffSearch.value = selectedOption.textContent;
                    const selectedDiv = staffOptions.querySelector(`.dropdown-option[data-value="${selectedOption.value}"]`);
                    if (selectedDiv) {
                        selectedDiv.classList.add('selected');
                    }
                }
            }
        });

        // Modal functions
        function showEditModal(checkupId, patientId, weight, height, temperature, pulse, bloodPressure, remark, staffId, date) {
            document.getElementById('modal_checkup_id').value = checkupId;
            document.getElementById('display_checkup_id').value = checkupId;
            document.getElementById('modal_hidden_patient_id').value = patientId;
            document.getElementById('modal_patient_id').value = patientId;
            document.getElementById('modal_weight').value = weight;
            document.getElementById('modal_height').value = height;
            document.getElementById('modal_temperature').value = temperature;
            document.getElementById('modal_pulse').value = pulse;
            document.getElementById('modal_blood_pressure').value = bloodPressure;
            document.getElementById('modal_remark').value = remark || '';
            document.getElementById('modal_staff_id').value = staffId;
            document.getElementById('modal_date').value = date;

            document.getElementById('editModal').style.display = 'block';

            // Add this to the showEditModal function
            const modalStaffSearch = document.getElementById('modal_staff_search');
            const modalStaffOptions = document.getElementById('modal_staff_options');
            const modalStaffHiddenSelect = document.getElementById('modal_staff_id');
            const modalStaffDropdownOptions = modalStaffOptions.querySelectorAll('.dropdown-option');

            modalStaffSearch.addEventListener('focus', function() {
                modalStaffDropdownOptions.forEach(option => {
                    option.style.display = 'block';
                });
                modalStaffOptions.classList.add('show');
            });

            modalStaffSearch.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                let hasVisibleOptions = false;

                modalStaffDropdownOptions.forEach(option => {
                    const optionText = option.textContent.toLowerCase();
                    if (optionText.includes(searchTerm)) {
                        option.style.display = 'block';
                        hasVisibleOptions = true;
                    } else {
                        option.style.display = 'none';
                    }
                });

                if (hasVisibleOptions || searchTerm.length === 0) {
                    modalStaffOptions.classList.add('show');
                } else {
                    modalStaffOptions.classList.remove('show');
                }
            });

            modalStaffDropdownOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const value = this.getAttribute('data-value');
                    const text = this.textContent;

                    modalStaffSearch.value = text;
                    modalStaffHiddenSelect.value = value;
                    modalStaffOptions.classList.remove('show');

                    modalStaffDropdownOptions.forEach(opt => {
                        opt.classList.remove('selected');
                    });
                    this.classList.add('selected');
                });
            });

            // Set the initial value in the modal
            modalStaffHiddenSelect.value = staffId;
            const selectedStaffOption = modalStaffHiddenSelect.querySelector('option:checked');
            if (selectedStaffOption) {
                modalStaffSearch.value = selectedStaffOption.textContent;
                const selectedDiv = modalStaffOptions.querySelector(`.dropdown-option[data-value="${selectedStaffOption.value}"]`);
                if (selectedDiv) {
                    selectedDiv.classList.add('selected');
                }
            }
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function confirmDelete(checkupId) {
            document.getElementById('delete_checkup_id').value = checkupId;
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

        function clearForm() {
            document.getElementById('checkupID').value = '';
            document.getElementById('patient_id').selectedIndex = 0;
            document.getElementById('patient_search').value = '';
            document.getElementById('weight').value = '';
            document.getElementById('height').value = '';
            document.getElementById('temperature').value = '';
            document.getElementById('pulse').value = '';
            document.getElementById('blood_pressure').value = '';
            document.getElementById('remark').value = '';
            document.getElementById('staff_id').selectedIndex = 0;
            document.getElementById('date').value = current_date;

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

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Current date for JavaScript
        const current_date = '<?php echo $current_date; ?>';

        // Add this to the existing JavaScript code
        function validateBloodPressureInput(input) {
            // Only allow numbers and forward slash
            input.value = input.value.replace(/[^0-9/]/g, '');

            // Ensure there's only one slash
            const parts = input.value.split('/');
            if (parts.length > 2) {
                input.value = parts[0] + '/' + parts[1];
            }
        }

        // Add event listeners for both blood pressure fields
        document.getElementById('blood_pressure').addEventListener('input', function(e) {
            validateBloodPressureInput(e.target);
        });

        document.getElementById('modal_blood_pressure').addEventListener('input', function(e) {
            validateBloodPressureInput(e.target);
        });

        // Optional: Add validation on form submit
        document.getElementById('checkupForm').addEventListener('submit', function(e) {
            const bpInput = document.getElementById('blood_pressure');
            if (bpInput.value && !/^\d{1,3}\/\d{1,3}$/.test(bpInput.value)) {
                alert('Please enter a valid blood pressure (e.g., 120/80)');
                e.preventDefault();
                bpInput.focus();
            }
        });

        document.getElementById('editForm').addEventListener('submit', function(e) {
            const bpInput = document.getElementById('modal_blood_pressure');
            if (bpInput.value && !/^\d{1,3}\/\d{1,3}$/.test(bpInput.value)) {
                alert('Please enter a valid blood pressure (e.g., 120/80)');
                e.preventDefault();
                bpInput.focus();
            }
        });

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
    </script>
</body>

</html>