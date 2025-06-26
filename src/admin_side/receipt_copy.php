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

// Function to generate next receipt ID
function generateReceiptID($conn)
{
    $sql = "SELECT MAX(receipt_id) AS max_id FROM receipt";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    // If no receipt exists, start with R0001
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

// Function to calculate total amount from selected services
function calculateTotalAmount($conn, $selected_services)
{
    $total = 0;
    if (!empty($selected_services)) {
        foreach ($selected_services as $service_id) {
            // Get service price
            $price_query = "SELECT service_fee FROM service_type WHERE service_type_id = '$service_id'";
            $price_result = mysqli_query($conn, $price_query);
            if ($price_row = mysqli_fetch_assoc($price_result)) {
                $total += $price_row['service_fee'];
            }
        }
    }
    return $total;
}


// Validation checks
$errors = '';
$message = '';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
    $staff_id = mysqli_real_escape_string($conn, $_POST['staff_id']);
    $receipt_date = mysqli_real_escape_string($conn, $_POST['receipt_date']);
    $remark = mysqli_real_escape_string($conn, $_POST['remark']);

    // Parse selected services with quantities
    $selected_services = [];
    if (isset($_POST['services']) && is_array($_POST['services'])) {
        foreach ($_POST['services'] as $service_id) {
            $selected_services[] = mysqli_real_escape_string($conn, $service_id);
        }
    }

    // Action based on button click
    if (isset($_POST['save_button'])) {
        // Validate required fields
        if (empty($patient_id)) {
            $errors = "Please select a patient.";
        } elseif (empty($staff_id)) {
            $errors = "Please select a staff member.";
        } elseif (empty($receipt_date)) {
            $errors = "Please select a receipt date.";
        } elseif (empty($selected_services)) {
            $errors = "Please select at least one service.";
        }

        if (empty($errors)) {
            // Calculate total amount
            $total_amount = calculateTotalAmount($conn, $selected_services);

            // Generate new receipt ID
            $receipt_id = generateReceiptID($conn);

            // Start transaction
            mysqli_begin_transaction($conn);

            try {
                // Insert into receipt table
                $insert_query = "INSERT INTO receipt (receipt_id, patient_id, total_amount, staff_id, date, remark) 
                               VALUES ('$receipt_id', '$patient_id', '$total_amount', '$staff_id', '$receipt_date', '$remark')";

                if (mysqli_query($conn, $insert_query)) {
                    // Insert selected services into receipt_service_type table
                    foreach ($selected_services as $service_id) {
                        // Verify service exists before inserting
                        $verify_service = "SELECT service_type_id FROM service_type WHERE service_type_id = '$service_id'";
                        $verify_result = mysqli_query($conn, $verify_service);

                        if (mysqli_num_rows($verify_result) > 0) {
                            $service_insert = "INSERT INTO receipt_service_type (receipt_id, service_type_id) 
                                         VALUES ('$receipt_id', '$service_id')";
                            if (!mysqli_query($conn, $service_insert)) {
                                throw new Exception("Error inserting service association: " . mysqli_error($conn));
                            }
                        }
                    }

                    mysqli_commit($conn);
                    $message = "Receipt created successfully! Total Amount: " . number_format($total_amount, 0) . " LAK";

                    // Clear form variables
                    $receipt_id = $patient_id = $staff_id = $receipt_date = $remark = '';
                    $selected_services = [];
                } else {
                    mysqli_rollback($conn);
                    $errors = "Error creating receipt: " . mysqli_error($conn);
                }
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $errors = "Error creating receipt: " . $e->getMessage();
            }
        }
    }

    // Update functionality
    if (isset($_POST['update_button']) && !empty($_POST['receipt_id'])) {
        $receipt_id = mysqli_real_escape_string($conn, $_POST['receipt_id']);

        // Validate required fields
        if (empty($patient_id)) {
            $errors = "Please select a patient.";
        } elseif (empty($staff_id)) {
            $errors = "Please select a staff member.";
        } elseif (empty($receipt_date)) {
            $errors = "Please select a receipt date.";
        } elseif (empty($selected_services)) {
            $errors = "Please select at least one service.";
        }

        if (empty($errors)) {
            // Calculate total amount
            $total_amount = calculateTotalAmount($conn, $selected_services);

            // Start transaction
            mysqli_begin_transaction($conn);

            try {
                // Update receipt table
                $update_query = "UPDATE receipt 
                               SET patient_id = '$patient_id', 
                                   total_amount = '$total_amount',
                                   staff_id = '$staff_id', 
                                   date = '$receipt_date', 
                                   remark = '$remark' 
                               WHERE receipt_id = '$receipt_id'";

                if (mysqli_query($conn, $update_query)) {
                    // Delete existing service associations
                    $delete_services = "DELETE FROM receipt_service_type WHERE receipt_id = '$receipt_id'";
                    mysqli_query($conn, $delete_services);

                    // Insert new service associations
                    foreach ($selected_services as $service_id) {
                        // Verify service exists before inserting
                        $verify_service = "SELECT service_type_id FROM service_type WHERE service_type_id = '$service_id'";
                        $verify_result = mysqli_query($conn, $verify_service);

                        if (mysqli_num_rows($verify_result) > 0) {
                            $service_insert = "INSERT INTO receipt_service_type (receipt_id, service_type_id) 
                         VALUES ('$receipt_id', '$service_id')";
                            if (!mysqli_query($conn, $service_insert)) {
                                throw new Exception("Error updating service association: " . mysqli_error($conn));
                            }
                        }
                    }

                    mysqli_commit($conn);
                    $message = "Receipt updated successfully! Total Amount: " . number_format($total_amount, 0) . " LAK";

                    // Clear form variables
                    $receipt_id = $patient_id = $staff_id = $receipt_date = $remark = '';
                    $selected_services = [];
                } else {
                    mysqli_rollback($conn);
                    $errors = "Error updating receipt: " . mysqli_error($conn);
                }
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $errors = "Error updating receipt: " . $e->getMessage();
            }
        }
    }

    // Delete functionality
    if (isset($_POST['delete_button']) && !empty($_POST['receipt_id'])) {
        $receipt_id = mysqli_real_escape_string($conn, $_POST['receipt_id']);

        // Start transaction
        mysqli_begin_transaction($conn);

        try {
            // Delete from receipt_service_type table first (foreign key constraint)
            $delete_services = "DELETE FROM receipt_service_type WHERE receipt_id = '$receipt_id'";
            mysqli_query($conn, $delete_services);

            // Delete from receipt table
            $delete_query = "DELETE FROM receipt WHERE receipt_id = '$receipt_id'";

            if (mysqli_query($conn, $delete_query)) {
                mysqli_commit($conn);
                $message = "Receipt deleted successfully!";
            } else {
                mysqli_rollback($conn);
                $errors = "Error deleting receipt: " . mysqli_error($conn);
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors = "Error deleting receipt: " . $e->getMessage();
        }
    }
}

// Search functionality
$search_results = [];
$no_results_message = "";
$is_search = false;

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $is_search = true;
    $search_term = mysqli_real_escape_string($conn, $_GET['search']);
    $search_query = "SELECT r.*, p.first_name, p.last_name, s.first_name as staff_first_name, s.last_name as staff_last_name
                     FROM receipt r 
                     LEFT JOIN patient p ON r.patient_id = p.patient_id
                     LEFT JOIN staff s ON r.staff_id = s.staff_id
                     WHERE r.receipt_id LIKE '%$search_term%' 
                     OR p.first_name LIKE '%$search_term%' 
                     OR p.last_name LIKE '%$search_term%' 
                     OR r.remark LIKE '%$search_term%'
                     ORDER BY r.date DESC";
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

// Fetch all receipts only if no search is performed
if (!$is_search && empty($search_results)) {
    $all_receipts_query = "SELECT r.*, p.first_name, p.last_name, s.first_name as staff_first_name, s.last_name as staff_last_name
                          FROM receipt r 
                          LEFT JOIN patient p ON r.patient_id = p.patient_id
                          LEFT JOIN staff s ON r.staff_id = s.staff_id
                          ORDER BY r.date DESC";
    $all_receipts_result = mysqli_query($conn, $all_receipts_query);

    while ($row = mysqli_fetch_assoc($all_receipts_result)) {
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
                WHERE position IN ('Admin') 
                ORDER BY position, first_name, last_name";
$staff_result = mysqli_query($conn, $staff_query);
$staff = [];
while ($row = mysqli_fetch_assoc($staff_result)) {
    $staff[$row['staff_id']] = $row['staff_id'] . ' - ' . $row['first_name'] . ' ' . $row['last_name'] . ' (' . $row['position'] . ')';
}

// Fetch service types for checkboxes
$services_query = "SELECT service_type_id, service_name, service_fee  FROM service_type ORDER BY service_name";
$services_result = mysqli_query($conn, $services_query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt Management</title>
    <link rel="stylesheet" href="patient_management.css">
    <style>
        .services-group {
            grid-column: span 2;
        }

        .services-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 10px;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 4px;
        }

        .service-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 5px;
            border: 1px solid #eee;
            border-radius: 4px;
        }

        .service-checkbox input[type="checkbox"] {
            margin: 0;
        }

        .service-checkbox label {
            margin: 0;
            font-size: 14px;
            cursor: pointer;
            flex: 1;
        }

        .service-price {
            font-weight: bold;
            color: #2563eb;
        }

        .quantity-input {
            width: 60px;
            padding: 2px 5px;
            border: 1px solid #ddd;
            border-radius: 3px;
            text-align: center;
        }

        .total-amount-display {
            background-color: #f0f9ff;
            border: 2px solid #2563eb;
            padding: 10px;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            color: #1e40af;
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

                <li><a href="eyes_check_management.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        Eyes Check</a>

                <li class="active"><a href="receipt.php">
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
                <h1>Receipt Management</h1>
                <button class="new-patient-button" onclick="clearForm()">+ New Receipt</button>
            </div>

            <?php if ($message): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($errors): ?>
                <div class="error"><?php echo $errors; ?></div>
            <?php endif; ?>

            <!-- Receipt Form -->
            <form method="POST" action="" id="receiptForm">
                <div class="patient-form">
                    <div class="form-group">
                        <label for="receiptID">Receipt ID</label>
                        <input type="text" id="receiptID" name="receipt_id" readonly>
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
                        <label for="receiptDate">Receipt Date</label>
                        <input type="date" id="receiptDate" name="receipt_date" required>
                    </div>
                    <div class="form-group">
                        <label for="remark">Remark</label>
                        <textarea id="remark" name="remark" rows="2" placeholder="Enter any additional notes..."></textarea>
                    </div>
                    <div class="form-group services-group">
                        <label>Services</label>
                        <div class="services-container">
                            <?php
                            mysqli_data_seek($services_result, 0);
                            while ($service = mysqli_fetch_assoc($services_result)): ?>
                                <div class="service-checkbox">
                                    <input type="checkbox"
                                        id="service_<?php echo $service['service_type_id']; ?>"
                                        name="services[]"
                                        value="<?php echo $service['service_type_id']; ?>"
                                        data-price="<?php echo $service['service_fee']; ?>"
                                        onchange="updateTotalAmount()">
                                    <label for="service_<?php echo $service['service_type_id']; ?>">
                                        <?php echo htmlspecialchars($service['service_name']); ?>
                                        <span class="service-price">(<?php echo number_format($service['service_fee']); ?> LAK)</span>
                                    </label>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Total Amount</label>
                        <div class="total-amount-display" id="totalAmountDisplay">0 LAK</div>
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
                    <input type="search" name="search" placeholder="Search receipts by ID, patient name, or remark..."
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit">Search</button>
                </form>
            </div>

            <?php if (!empty($no_results_message)): ?>
                <div class="alert alert-info">
                    <?php echo $no_results_message; ?>
                </div>
            <?php endif; ?>

            <!-- Receipt Table -->
            <table class="patient-table">
                <?php if (!empty($search_results)): ?>
                    <thead>
                        <tr>
                            <th>RECEIPT ID</th>
                            <th>PATIENT</th>
                            <th>STAFF</th>
                            <th>DATE</th>
                            <th>TOTAL AMOUNT</th>
                            <th>SERVICES</th>
                            <th>REMARK</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($search_results as $receipt): ?>
                            <?php
                            // Get associated services for this receipt
                            $service_query = "SELECT st.service_name, st.service_fee 
                 FROM receipt_service_type rst 
                 JOIN service_type st ON rst.service_type_id = st.service_type_id 
                 WHERE rst.receipt_id = '" . $receipt['receipt_id'] . "'";
                            $service_result = mysqli_query($conn, $service_query);
                            $services = [];
                            while ($service_row = mysqli_fetch_assoc($service_result)) {
                                $services[] = $service_row['service_name'];
                            }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($receipt['receipt_id']); ?></td>
                                <td><?php echo htmlspecialchars($receipt['first_name'] . ' ' . $receipt['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($receipt['staff_first_name'] . ' ' . $receipt['staff_last_name']); ?></td>
                                <td><?php echo htmlspecialchars($receipt['date']); ?></td>
                                <td><strong><?php echo number_format($receipt['total_amount'], 0); ?> LAK</strong></td>
                                <td><?php echo htmlspecialchars(implode(', ', $services)); ?></td>
                                <td><?php echo htmlspecialchars(substr($receipt['remark'], 0, 30)) . (strlen($receipt['remark']) > 30 ? '...' : ''); ?></td>
                                <td>
                                    <a href="#" onclick="fillForm('<?php echo htmlspecialchars($receipt['receipt_id']); ?>', 
                                        '<?php echo htmlspecialchars($receipt['patient_id']); ?>', 
                                        '<?php echo htmlspecialchars($receipt['staff_id']); ?>', 
                                        '<?php echo htmlspecialchars($receipt['date']); ?>', 
                                        '<?php echo htmlspecialchars($receipt['remark']); ?>')">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
            </table>
        <?php endif; ?>
        </main>
    </div>

    <script>
        function updateTotalAmount() {
            let total = 0;
            const serviceCheckboxes = document.querySelectorAll('input[name="services[]"]:checked');

            serviceCheckboxes.forEach(checkbox => {
                total += parseFloat(checkbox.dataset.price);
            });

            // Format the number with thousand separators (remove decimal places)
            document.getElementById('totalAmountDisplay').textContent =
                total.toLocaleString('en-US', {
                    maximumFractionDigits: 0
                }) + ' LAK';
        }

        function fillForm(receiptId, patientId, staffId, date, remark) {
            document.getElementById('receiptID').value = receiptId;
            // document.getElementById('patientSelect').value = patientId;
            // Set patient dropdown
            const patientSelect = document.getElementById('patient_id');
            patientSelect.value = patientId;
            const selectedPatientOption = patientSelect.querySelector(`option[value="${patientId}"]`);
            if (selectedPatientOption) {
                document.getElementById('patient_search').value = selectedPatientOption.textContent;
            }

            // document.getElementById('staffSelect').value = staffId;
            // Set staff dropdown
            const staffSelect = document.getElementById('staff_id');
            staffSelect.value = staffId;
            const selectedStaffOption = staffSelect.querySelector(`option[value="${staffId}"]`);
            if (selectedStaffOption) {
                document.getElementById('staff_search').value = selectedStaffOption.textContent;
            }

            document.getElementById('receiptDate').value = date;
            document.getElementById('remark').value = remark;

            // Load associated services
            loadReceiptServices(receiptId);
        }

        function loadReceiptServices(receiptId) {
            // Clear all checkboxes first
            document.querySelectorAll('input[name="services[]"]').forEach(checkbox => {
                checkbox.checked = false;
            });

            // Fetch and check associated services via AJAX
            fetch('get_receipt_services.php?receipt_id=' + encodeURIComponent(receiptId))
                .then(response => response.json())
                .then(services => {
                    let total = 0;

                    services.forEach(service => {
                        const checkbox = document.getElementById('service_' + service.service_type_id);
                        if (checkbox) {
                            checkbox.checked = true;
                            total += parseFloat(checkbox.dataset.price);
                        }
                    });

                    // Update total with formatted number
                    document.getElementById('totalAmountDisplay').textContent =
                        total.toLocaleString('en-US', {
                            maximumFractionDigits: 0
                        }) + ' LAK';
                })
                .catch(error => {
                    console.error('Error loading receipt services:', error);
                });
        }

        function clearForm() {
            document.getElementById('receiptID').value = '';
            // document.getElementById('patientSelect').selectedIndex = 0;
            // document.getElementById('staffSelect').selectedIndex = 0;
            document.getElementById('patient_id').selectedIndex = 0;
            document.getElementById('patient_search').value = '';
            document.getElementById('staff_id').selectedIndex = 0;
            document.getElementById('staff_search').value = '';
            document.getElementById('receiptDate').value = '';
            document.getElementById('remark').value = '';

            // Clear all service checkboxes
            document.querySelectorAll('input[name="services[]"]').forEach(checkbox => {
                checkbox.checked = false;
            });

            updateTotalAmount();
            document.getElementById('patientSelect').focus();
        }

        // Form validation before submission
        document.getElementById('receiptForm').addEventListener('submit', function(e) {
            const patientSelect = document.getElementById('patientSelect');
            const staffSelect = document.getElementById('staffSelect');
            const dateInput = document.getElementById('receiptDate');
            const selectedServices = document.querySelectorAll('input[name="services[]"]:checked');

            if (patientSelect.value === '') {
                e.preventDefault();
                alert('Please select a patient.');
                patientSelect.focus();
                return false;
            }

            if (staffSelect.value === '') {
                e.preventDefault();
                alert('Please select a staff member.');
                staffSelect.focus();
                return false;
            }

            if (dateInput.value === '') {
                e.preventDefault();
                alert('Please select a receipt date.');
                dateInput.focus();
                return false;
            }

            if (selectedServices.length === 0) {
                e.preventDefault();
                alert('Please select at least one service.');
                return false;
            }

            return true;
        });

        // Initialize total amount calculation on page load
        document.addEventListener("DOMContentLoaded", function() {
            // Set max date to today for receipt date
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0');
            const dd = String(today.getDate()).padStart(2, '0');
            const maxDate = `${yyyy}-${mm}-${dd}`;
            document.getElementById('receiptDate').setAttribute('max', maxDate);

            // Initialize total amount
            updateTotalAmount();
        });

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        function toggleSubmenu(element) {
            event.preventDefault();
            const parent = element.parentElement;
            parent.classList.toggle('active');
        }

        // Custom dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Patient dropdown
            const patientSearch = document.getElementById('patient_search');
            const patientOptions = document.getElementById('patient_options');
            const hiddenPatientSelect = document.getElementById('patient_id');
            const patientDropdownOptions = patientOptions.querySelectorAll('.dropdown-option');

            // Staff dropdown
            const staffSearch = document.getElementById('staff_search');
            const staffOptions = document.getElementById('staff_options');
            const hiddenStaffSelect = document.getElementById('staff_id');
            const staffDropdownOptions = staffOptions.querySelectorAll('.dropdown-option');

            // Initialize both dropdowns
            initDropdown(patientSearch, patientOptions, hiddenPatientSelect, patientDropdownOptions);
            initDropdown(staffSearch, staffOptions, hiddenStaffSelect, staffDropdownOptions);

            function initDropdown(searchInput, optionsContainer, hiddenSelect, dropdownOptions) {
                searchInput.addEventListener('focus', function() {
                    dropdownOptions.forEach(option => {
                        option.style.display = 'block';
                    });
                    optionsContainer.classList.add('show');
                });

                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
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
                        optionsContainer.classList.add('show');
                    } else {
                        optionsContainer.classList.remove('show');
                    }
                });

                dropdownOptions.forEach(option => {
                    option.addEventListener('click', function() {
                        const value = this.getAttribute('data-value');
                        const text = this.textContent;

                        searchInput.value = text;
                        hiddenSelect.value = value;
                        optionsContainer.classList.remove('show');

                        dropdownOptions.forEach(opt => {
                            opt.classList.remove('selected');
                        });
                        this.classList.add('selected');
                    });
                });

                document.addEventListener('click', function(e) {
                    if (!e.target.closest('.custom-dropdown')) {
                        optionsContainer.classList.remove('show');
                    }
                });

                if (hiddenSelect.value) {
                    const selectedOption = hiddenSelect.querySelector('option:checked');
                    if (selectedOption) {
                        searchInput.value = selectedOption.textContent;
                        const selectedDiv = optionsContainer.querySelector(`.dropdown-option[data-value="${selectedOption.value}"]`);
                        if (selectedDiv) {
                            selectedDiv.classList.add('selected');
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>