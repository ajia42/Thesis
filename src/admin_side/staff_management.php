<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: signin_admin.php');
    exit();
}

// Include database configuration
include("../db_config.php");

// Function to generate next staff ID
function generateStaffID($conn)
{
    $sql = "SELECT MAX(staff_id) AS max_id FROM staff";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    // If no staff exists, start with S0001
    if (empty($row['max_id'])) {
        return 'S0001';
    }

    // Extract the numeric part and increment
    $lastID = $row['max_id'];
    $numPart = intval(substr($lastID, 1));
    $newNumPart = $numPart + 1;

    // Format the new ID with leading zeros
    return 'S' . str_pad($newNumPart, 4, '0', STR_PAD_LEFT);
}

// Validation checks
$errors = '';
$message = '';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $dob = mysqli_real_escape_string($conn, $_POST['dob']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $position = mysqli_real_escape_string($conn, $_POST['position']);

    // Action based on button click
    if (isset($_POST['save_button'])) {
        // Check if phone exists in staff table
        $phone_check = "SELECT * FROM staff WHERE phone = '$phone'";
        $phone_result = mysqli_query($conn, $phone_check);
        if (mysqli_num_rows($phone_result) > 0) {
            $errors = "Phone number already exists in staff records.";
        }

        if (empty($errors)) {
            // Start transaction
            mysqli_begin_transaction($conn);

            try {
                // Generate new staff ID
                $staff_id = generateStaffID($conn);

                // Prepare INSERT query for staff
                $insert_staff = "INSERT INTO staff (staff_id, first_name, last_name, gender, phone, dob, address, position) 
                                 VALUES ('$staff_id', '$first_name', '$last_name', '$gender', '$phone', '$dob', '$address', '$position')";

                if (mysqli_query($conn, $insert_staff)) {
                    mysqli_commit($conn);
                    $message = "Staff added successfully!";
                    $staff_id = $first_name = $last_name = $gender = $phone = $dob = $address = $position = '';
                } else {
                    throw new Exception("Error adding staff: " . mysqli_error($conn));
                }
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $errors = $e->getMessage();
            }
        }
    }

    // Handle update functionality
    if (isset($_POST['update_button']) && !empty($_POST['staff_id'])) {
        $staff_id = mysqli_real_escape_string($conn, $_POST['staff_id']);
        $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
        $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
        $gender = mysqli_real_escape_string($conn, $_POST['gender']);
        $new_phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $original_phone = mysqli_real_escape_string($conn, $_POST['original_phone']);
        $dob = mysqli_real_escape_string($conn, $_POST['dob']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $position = mysqli_real_escape_string($conn, $_POST['position']);

        // Validate phone number
        if (!preg_match('/^20\d{8}$/', $new_phone)) {
            $errors = "Phone must start with 20 and be 10 digits (e.g., 2012345678)";
        } else {
            // Start transaction
            mysqli_begin_transaction($conn);

            try {
                // Check if phone number is being changed
                if ($new_phone != $original_phone) {
                    // Check if new phone exists in staff table (excluding current record)
                    $staff_check = "SELECT * FROM staff WHERE phone = '$new_phone' AND phone != '$original_phone'";
                    $staff_result = mysqli_query($conn, $staff_check);

                    if (mysqli_num_rows($staff_result) > 0) {
                        throw new Exception("Phone number already registered in staff records");
                    }
                }

                // Update staff table
                $update_staff = "UPDATE staff SET 
                              first_name = '$first_name', 
                              last_name = '$last_name', 
                              gender = '$gender', 
                              phone = '$new_phone', 
                              dob = '$dob', 
                              address = '$address',
                              position = '$position'
                              WHERE staff_id = '$staff_id'";

                if (!mysqli_query($conn, $update_staff)) {
                    throw new Exception("Error updating staff: " . mysqli_error($conn));
                }

                // If we got here, all queries were successful - commit transaction
                mysqli_commit($conn);
                $message = "Staff updated successfully!";

                // Clear the form
                $staff_id = $first_name = $last_name = $gender = $new_phone = $dob = $address = $position = '';
            } catch (Exception $e) {
                // Something went wrong - rollback transaction
                mysqli_rollback($conn);
                $errors = $e->getMessage();
            }
        }
    }

    // Delete functionality
    if (isset($_POST['delete_button']) && !empty($_POST['staff_id'])) {
        $staff_id = mysqli_real_escape_string($conn, $_POST['staff_id']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);

        // Start transaction
        mysqli_begin_transaction($conn);

        try {
            // Delete the staff
            $delete_staff = "DELETE FROM staff WHERE staff_id = '$staff_id'";
            if (!mysqli_query($conn, $delete_staff)) {
                throw new Exception("Error deleting staff: " . mysqli_error($conn));
            }

            mysqli_commit($conn);
            $message = "Staff deleted successfully!";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors = $e->getMessage();
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
    $search_query = "SELECT * FROM staff 
                     WHERE staff_id LIKE '%$search_term%' 
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

// Fetch all staff only if no search is performed
if (!$is_search && empty($search_results)) {
    $all_staff_query = "SELECT * FROM staff";
    $all_staff_result = mysqli_query($conn, $all_staff_query);

    while ($row = mysqli_fetch_assoc($all_staff_result)) {
        $search_results[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management</title>
    <link rel="stylesheet" href="staff_management.css">
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
                <h1>Staff Management</h1>
                <button class="new-staff-button" name="new_staff" onclick="clearForm()">+ New Staff</button>
            </div>

            <?php if ($message): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($errors): ?>
                <div class="error"><?php echo $errors; ?></div>
            <?php endif; ?>

            <!-- Staff Form -->
            <form method="POST" action="" id="staffForm">
                <input type="hidden" id="original_phone" name="original_phone">
                <div class="staff-form">
                    <div class="form-group">
                        <label for="staffID">Staff ID</label>
                        <input type="text" id="staffID" name="staff_id" readonly>
                    </div>
                    <div class="form-group">
                        <label for="firstName">First Name</label>
                        <input type="text" id="firstName" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="lastName">Last Name</label>
                        <input type="text" id="lastName" name="last_name" required>
                    </div>
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender">
                            <option value="Male" selected>Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="position">Position</label>
                        <select id="position" name="position" required>
                            <option value="admin">Admin</option>
                            <option value="doctor">Doctor</option>
                            <option value="nurse">Nurse</option>
                            <option value="manager">Manager</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
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
                        <label for="dob">Date of Birth</label>
                        <input type="date" id="dob" name="dob" required>
                        <div id="dobError" class="error-message" style="display: none;"></div>
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address">
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="save-button" name="save_button" id="saveButton">Save</button>
                        <button type="submit" class="update-button" name="update_button" id="updateButton">Update</button>
                        <button type="submit" class="delete-button" name="delete_button" id="deleteButton">Delete</button>
                    </div>
                </div>
            </form>

            <!-- Search Form -->
            <div class="staff-list-header">
                <form method="GET" action="">
                    <input type="search" name="search" placeholder="Search staff by name or phone..."
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit">Search</button>
                </form>
            </div>

            <?php if (!empty($no_results_message)): ?>
                <div class="alert alert-info">
                    <?php echo $no_results_message; ?>
                </div>
            <?php endif; ?>

            <!-- Staff Table -->
            <table class="staff-table">
                <?php if (!empty($search_results)): ?>
                    <thead>
                        <tr>
                            <th>STAFF ID</th>
                            <th>FIRST NAME</th>
                            <th>LAST NAME</th>
                            <th>GENDER</th>
                            <th>POSITION</th>
                            <th>PHONE</th>
                            <th>DATE OF BIRTH</th>
                            <th>ADDRESS</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($search_results as $staff): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($staff['staff_id']); ?></td>
                                <td><?php echo htmlspecialchars($staff['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($staff['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($staff['gender']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($staff['position'])); ?></td>
                                <td><?php echo htmlspecialchars($staff['phone']); ?></td>
                                <td><?php echo htmlspecialchars($staff['dob']); ?></td>
                                <td><?php echo htmlspecialchars($staff['address']); ?></td>
                                <td>
                                    <a href="#" onclick="fillForm('<?php echo htmlspecialchars($staff['staff_id']); ?>', 
                                '<?php echo htmlspecialchars($staff['first_name']); ?>', 
                                '<?php echo htmlspecialchars($staff['last_name']); ?>', 
                                '<?php echo htmlspecialchars($staff['gender']); ?>', 
                                '<?php echo htmlspecialchars($staff['phone']); ?>', 
                                '<?php echo htmlspecialchars($staff['dob']); ?>', 
                                '<?php echo htmlspecialchars($staff['address']); ?>',
                                '<?php echo htmlspecialchars($staff['position']); ?>')">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
            </table>
        <?php endif; ?>
        </main>
    </div>

    <script>
        function fillForm(staffId, firstName, lastName, gender, phone, dob, address, position) {
            document.getElementById('staffID').value = staffId;
            document.getElementById('firstName').value = firstName;
            document.getElementById('lastName').value = lastName;
            document.getElementById('gender').value = gender;
            document.getElementById('phone').value = phone;
            document.getElementById('original_phone').value = phone;
            document.getElementById('dob').value = dob;
            document.getElementById('address').value = address;
            document.getElementById('position').value = position;

            // Disable save button, enable update and delete
            document.getElementById('saveButton').disabled = true;
            document.getElementById('updateButton').disabled = false;
            document.getElementById('deleteButton').disabled = false;

            // Scroll to form
            document.getElementById('staffForm').scrollIntoView({
                behavior: 'smooth'
            });
        }

        function clearForm() {
            document.getElementById('staffID').value = '';
            document.getElementById('firstName').value = '';
            document.getElementById('lastName').value = '';
            document.getElementById('gender').selectedIndex = 0;
            document.getElementById('phone').value = '';
            document.getElementById('original_phone').value = '';
            document.getElementById('dob').value = '';
            document.getElementById('address').value = '';
            document.getElementById('position').selectedIndex = 0;
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

            // If there's a staff ID in the form (from form submission error), 
            // we should disable save and enable update/delete
            if (document.getElementById('staffID').value) {
                document.getElementById('saveButton').disabled = true;
                document.getElementById('updateButton').disabled = false;
                document.getElementById('deleteButton').disabled = false;
            }

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

        // Add this to prevent form submission with wrong button states
        document.getElementById('staffForm').addEventListener('submit', function(e) {
            const staffId = document.getElementById('staffID').value;
            const isSave = e.submitter.name === 'save_button';
            const isUpdate = e.submitter.name === 'update_button';
            const isDelete = e.submitter.name === 'delete_button';

            if (isSave && staffId) {
                e.preventDefault();
                alert("Error: You're trying to save an existing record. Use Update instead.");
                return;
            }

            if ((isUpdate || isDelete) && !staffId) {
                e.preventDefault();
                alert("Error: No staff selected. Please select a staff to edit first.");
                return;
            }
        });

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        const phoneInput = document.getElementById("phone");
        const phoneError = document.getElementById("phoneError");
        const staffForm = document.getElementById("staffForm");

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
        staffForm.addEventListener("submit", function(e) {
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

            // Check age range (assuming staff should be between 0 and 120 years old)
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
        staffForm.addEventListener("submit", function(e) {
            if (dobInput.value) {
                const validation = validateDob(dobInput.value);
                if (!validation.valid) {
                    e.preventDefault();
                    showDobError(validation.message);
                    dobInput.focus();
                }
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
    </script>
</body>

</html>