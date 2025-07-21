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

// Function to generate next reception ID
function generateReceptionID($conn)
{
    $sql = "SELECT MAX(reception_id) AS max_id FROM reception";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    // If no reception exists, start with R0001
    if (empty($row['max_id'])) {
        return 'R0001';
    }

    // Extract the numeric part and increment
    $lastID = $row['max_id'];
    $numPart = intval(substr($lastID, 1));
    $newNumPart = $numPart + 1;

    // Format the new ID with leading zeros
    return 'R' . str_pad($newNumPart, 4, '0', STR_PAD_LEFT);
}

// Validation checks
$errors = '';
$message = '';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
    $patient_type = mysqli_real_escape_string($conn, $_POST['patient_type']);

    // Action based on button click
    if (isset($_POST['save_button'])) {
        // Generate new reception ID
        $reception_id = generateReceptionID($conn);

        // Prepare INSERT query
        $insert_query = "INSERT INTO reception 
                        (reception_id, patient_id, patient_type, create_at) 
                        VALUES ('$reception_id', '$patient_id', '$patient_type', CURRENT_TIMESTAMP)";

        if (mysqli_query($conn, $insert_query)) {
            $message = "Reception record added successfully!";
            $reception_id = $patient_id = $patient_type = '';
        } else {
            $errors = "Error adding reception record: " . mysqli_error($conn);
        }
    }

    // Update functionality
    if (isset($_POST['update_button']) && !empty($_POST['reception_id'])) {
        $reception_id = mysqli_real_escape_string($conn, $_POST['reception_id']);

        // Prepare UPDATE query
        $update_query = "UPDATE reception 
                        SET patient_id = '$patient_id',
                            patient_type = '$patient_type'
                        WHERE reception_id = '$reception_id'";

        if (mysqli_query($conn, $update_query)) {
            $message = "Reception record updated successfully!";
            $reception_id = $patient_id = $patient_type = '';
        } else {
            $errors = "Error updating reception record: " . mysqli_error($conn);
        }
    }

    // Delete functionality
    if (isset($_POST['delete_button']) && !empty($_POST['reception_id'])) {
        $reception_id = mysqli_real_escape_string($conn, $_POST['reception_id']);

        // Prepare DELETE query
        $delete_query = "DELETE FROM reception WHERE reception_id = '$reception_id'";

        if (mysqli_query($conn, $delete_query)) {
            $message = "Reception record deleted successfully!";
        } else {
            $errors = "Error deleting reception record: " . mysqli_error($conn);
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



// Search functionality
$search_query = "";
$search_results = [];
$no_results_message = "";
$is_search = false;

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $is_search = true;
    $search_term = mysqli_real_escape_string($conn, $_GET['search']);
    $search_query = "SELECT r.*, p.first_name, p.last_name 
                     FROM reception r
                     JOIN patient p ON r.patient_id = p.patient_id
                     WHERE r.reception_id LIKE '%$search_term%' 
                     OR p.first_name LIKE '%$search_term%' 
                     OR p.last_name LIKE '%$search_term%'
                     OR r.patient_type LIKE '%$search_term%'
                     OR r.create_at LIKE '%$search_term%'";
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

// Fetch all reception records only if no search is performed
if (!$is_search && empty($search_results)) {
    $all_receptions_query = "SELECT r.*, p.first_name, p.last_name 
                            FROM reception r
                            JOIN patient p ON r.patient_id = p.patient_id";
    $all_receptions_result = mysqli_query($conn, $all_receptions_query);

    while ($row = mysqli_fetch_assoc($all_receptions_result)) {
        $search_results[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reception Management</title>
    <link rel="stylesheet" href="patient_management.css">
    <link rel="icon" href="../images/logo.svg" type="image/svg+xml">

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

                <li class="active"><a href="reception_management.php">
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
                <h1>ຕ້ອນຮັບ</h1>
                <button class="new-patient-button" name="new_reception" onclick="clearForm()">+ ເພີ່ມຕ້ອນຮັບ</button>
            </div>

            <?php if ($message): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($errors): ?>
                <div class="error"><?php echo $errors; ?></div>
            <?php endif; ?>

            <!-- Reception Form -->
            <form method="POST" action="" id="receptionForm">
                <div class="patient-form">
                    <div class="form-group">
                        <label for="receptionID">ລະຫັດຕ້ອນຮັບ</label>
                        <input type="text" id="receptionID" name="reception_id" readonly>
                    </div>

                    <div class="form-group">
                        <label for="patient_id">ຄົນເຈັບ</label>
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
                        <label for="patient_type">ປະເພດເຂົ້າມາ</label>
                        <select id="patient_type" name="patient_type" required>
                            <option value="walk-in">Walk-in</option>
                            <option value="online">Online</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="create_at">ເວລາທີ່ເຂົ້າມາ</label>
                        <input type="text" id="create_at" name="create_at" class="readonly-field" readonly>
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
                    <input type="search" name="search" placeholder="Search reception records by patient, type or date..."
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit">ຄົ້ນຫາ</button>
                </form>
            </div>

            <?php if (!empty($no_results_message)): ?>
                <div class="alert alert-info">
                    <?php echo $no_results_message; ?>
                </div>
            <?php endif; ?>

            <!-- Reception Table -->
            <table class="patient-table">
                <?php if (!empty($search_results)): ?>
                    <thead>
                        <tr>
                            <th>ລະຫັດຕ້ອນຮັບ</th>
                            <th>ຊື່ຄົນເຈັບ</th>
                            <th>ປະເພດເຂົ້າມາ</th>
                            <th>ເວລາທີ່ເຂົ້າມາ</th>
                            <th>ສະແດງຂໍ້ມູນ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($search_results as $reception): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reception['reception_id']); ?></td>
                                <td><?php echo htmlspecialchars($reception['first_name'] . ' ' . $reception['last_name']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($reception['patient_type'])); ?></td>
                                <td><?php echo htmlspecialchars($reception['create_at']); ?></td>
                                <td>
                                    <a href="#" onclick="fillForm(
                                        '<?php echo htmlspecialchars($reception['reception_id']); ?>',
                                        '<?php echo htmlspecialchars($reception['patient_id']); ?>',
                                        '<?php echo htmlspecialchars($reception['patient_type']); ?>',
                                        '<?php echo htmlspecialchars($reception['create_at']); ?>'
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
        function fillForm(receptionId, patientId, patientType, createAt) {
            document.getElementById('receptionID').value = receptionId;
            document.getElementById('patient_id').value = patientId;
            document.getElementById('patient_type').value = patientType;
            document.getElementById('create_at').value = createAt;

            // Set the patient search input value
            const selectedOption = document.querySelector(`#patient_id option[value="${patientId}"]`);
            if (selectedOption) {
                document.getElementById('patient_search').value = selectedOption.textContent;
            }

            // Disable save button, enable update and delete
            document.getElementById('saveButton').disabled = true;
            document.getElementById('updateButton').disabled = false;
            document.getElementById('deleteButton').disabled = false;
        }

        function clearForm() {
            document.getElementById('receptionID').value = '';
            document.getElementById('patient_id').selectedIndex = 0;
            document.getElementById('patient_search').value = '';
            document.getElementById('patient_type').selectedIndex = 0;
            document.getElementById('create_at').value = '';

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

            // If there's a reception ID in the form (from form submission error), 
            // disable save and enable update/delete
            if (document.getElementById('receptionID').value) {
                document.getElementById('saveButton').disabled = true;
                document.getElementById('updateButton').disabled = false;
                document.getElementById('deleteButton').disabled = false;
            }
        });

        // Prevent form submission with wrong button states
        document.getElementById('receptionForm').addEventListener('submit', function(e) {
            const receptionId = document.getElementById('receptionID').value;
            const isSave = e.submitter.name === 'save_button';
            const isUpdate = e.submitter.name === 'update_button';
            const isDelete = e.submitter.name === 'delete_button';

            if (isSave && receptionId) {
                e.preventDefault();
                alert("Error: You're trying to save an existing record. Use Update instead.");
                return;
            }

            if ((isUpdate || isDelete) && !receptionId) {
                e.preventDefault();
                alert("Error: No reception record selected. Please select a record to edit first.");
                return;
            }
        });

        // Prevent form resubmission on page refresh
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
    </script>
</body>

</html>