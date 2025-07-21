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

// Function to generate next patient ID
function generatePatientID($conn)
{
    $sql = "SELECT MAX(disease_id) AS max_id FROM disease";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    // If no patient exists, start with P0001
    if (empty($row['max_id'])) {
        return 'DS001';
    }

    // Extract the numeric part and increment
    $lastID = $row['max_id'];
    $numPart = intval(substr($lastID, 2));
    $newNumPart = $numPart + 1;

    // Format the new ID with leading zeros
    return 'DS' . str_pad($newNumPart, 3, '0', STR_PAD_LEFT);
}

// Validation checks
$validate_email = "";
$validate_phone = "";
$errors = '';
$message = '';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);

    // Action based on button click
    if (isset($_POST['save_button'])) {

        if (empty($errors)) {
            // Generate new patient ID
            $patient_id = generatePatientID($conn);

            // Prepare INSERT query
            $insert_query = "INSERT INTO disease (disease_id, disease_name, description) 
                         VALUES ('$patient_id', '$first_name', '$phone')";

            if (mysqli_query($conn, $insert_query)) {
                $message = "Disease added successfully!";
                // echo "<script>alert('Patient added successfully!');</script>";
                $patient_id = $first_name =  $phone = '';
            } else {
                // echo "<script>alert('Error adding patient: " . mysqli_error($conn) . "');</script>";
                $errors = "Error adding disease: " . mysqli_error($conn);
            }
        }
    }

    // Update functionality
    if (isset($_POST['update_button']) && !empty($_POST['patient_id'])) {
        $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);

        if (empty($errors)) {
            // Prepare UPDATE query
            $update_query = "UPDATE disease 
        SET disease_name = '$first_name', 
        description = '$phone'  
        WHERE disease_id = '$patient_id'";

            if (mysqli_query($conn, $update_query)) {
                // echo "<script>alert('Patient updated successfully!');</script>";
                $message = "Disease updated successfully!";
                // $patient_id = $first_name = $last_name = $gender = $email = $phone = $dob = $address = '';
            } else {
                // echo "<script>alert('Error updating patient: " . mysqli_error($conn) . "');</script>";
                $errors = "Error updating disease: " . mysqli_error($conn);
            }
        }
    }

    // Delete functionality
    if (isset($_POST['delete_button']) && !empty($_POST['patient_id'])) {
        $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);

        // List of all tables that reference disease with their actual column names
        $related_tables = [
            'treatment_disease' => ['column' => 'disease_id', 'name' => 'treatment disease records'],
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
            $errors = "Cannot delete disease because it has existing: " .
                implode(", ", $existing_records) . ". " .
                "Please delete these records first or contact your system administrator.";
        } else {
            // Prepare DELETE query
            $delete_query = "DELETE FROM disease WHERE disease_id = '$patient_id'";

            if (mysqli_query($conn, $delete_query)) {
                $message = "Disease deleted successfully!";
            } else {
                $errors = "Error deleting disease: " . mysqli_error($conn);
            }
        }
    }
}


// Search functionality - REPLACE your current search code with this block
$search_query = "";
$search_results = [];
$no_results_message = "";
$is_search = false;

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $is_search = true;
    $search_term = mysqli_real_escape_string($conn, $_GET['search']);
    $search_query = "SELECT * FROM disease
                     WHERE disease_id LIKE '%$search_term%' 
                     OR disease_name LIKE '%$search_term%' 
                     OR description LIKE '%$search_term%'";
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
    $all_patients_query = "SELECT * FROM disease";
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
    <title>Disease Management</title>
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
                <h1>ຈັດການຂໍ້ມູນພະຍາດ</h1>
                <button class="new-patient-button" name="new_patient" onclick="clearForm()">+ ເພີ່ມພະຍາດ</button>
            </div>

            <?php if ($message): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($errors): ?>
                <div class="error"><?php echo $errors; ?></div>
            <?php endif; ?>

            <!-- Patient Form -->
            <form method="POST" action="" id="patientForm">
                <div class="patient-form">
                    <div class="form-group">
                        <label for="patientID">ລະຫັດພະຍາດ</label>
                        <input type="text" id="patientID" name="patient_id" readonly>
                    </div>
                    <div class="form-group">
                        <label for="firstName">ຊື່ພະຍາດ</label>
                        <input type="text" id="firstName" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">ຄໍາອະທິບາຍ</label>
                        <!-- <input type="number" id="phone" name="phone" required> -->
                        <input
                            type="text"
                            id="phone"
                            name="phone"
                            placeholder="optional" />
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
                    <input type="search" name="search" placeholder="Search services by name or id..."
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit">Search</button>
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
                            <th>ລະຫັດພະຍາດ</th>
                            <th>ຊື່ພະຍາດ</th>
                            <th>ຄໍາອະທິບາຍ</th>
                            <th>ສະແດງຂໍ້ມູນ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($search_results as $patient): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($patient['disease_id']); ?></td>
                                <td><?php echo htmlspecialchars($patient['disease_name']); ?></td>
                                <td><?php echo htmlspecialchars($patient['description']); ?></td>
                                <td>
                                    <a href="#" onclick="fillForm('<?php echo htmlspecialchars($patient['disease_id']); ?>', 
                                '<?php echo htmlspecialchars($patient['disease_name']); ?>', 
                                '<?php echo htmlspecialchars($patient['description']); ?>')">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
            </table>
        <?php endif; ?>
        </main>
    </div>

    <script>
        function fillForm(patientId, firstName, phone) {
            document.getElementById('patientID').value = patientId;
            document.getElementById('firstName').value = firstName;
            document.getElementById('phone').value = phone;

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
            document.getElementById('patientID').value = ''; // Clear Patient ID
            document.getElementById('firstName').value = ''; // Clear First Name
            document.getElementById('phone').value = ''; // Clear Phone
            // Focus on first name input
            document.getElementById('firstName').focus();

            // Enable save button, disable update and delete
            document.getElementById('saveButton').disabled = false;
            document.getElementById('updateButton').disabled = true;
            document.getElementById('deleteButton').disabled = true;
        }

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

            // Initially disable update and delete buttons
            document.getElementById('updateButton').disabled = true;
            document.getElementById('deleteButton').disabled = true;

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