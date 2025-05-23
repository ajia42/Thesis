<?php
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
        foreach ($selected_services as $service_data) {
            $service_id = $service_data['service_id'];
            $quantity = intval($service_data['quantity']);

            // Get service price
            $price_query = "SELECT service_fee FROM service_type WHERE service_type_id = '$service_id'";
            $price_result = mysqli_query($conn, $price_query);
            if ($price_row = mysqli_fetch_assoc($price_result)) {
                $total += ($price_row['service_fee'] * $quantity);
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
            $quantity_key = 'quantity_' . $service_id;
            $quantity = isset($_POST[$quantity_key]) ? intval($_POST[$quantity_key]) : 1;
            if ($quantity > 0) {
                $selected_services[] = [
                    'service_id' => mysqli_real_escape_string($conn, $service_id),
                    'quantity' => $quantity
                ];
            }
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
                    foreach ($selected_services as $service_data) {
                        $service_id = $service_data['service_id'];
                        $quantity = $service_data['quantity'];

                        // Verify service exists before inserting
                        $verify_service = "SELECT service_type_id FROM service_type WHERE service_type_id = '$service_id'";
                        $verify_result = mysqli_query($conn, $verify_service);

                        if (mysqli_num_rows($verify_result) > 0) {
                            $service_insert = "INSERT INTO receipt_service_type (receipt_id, service_type_id, quantity) 
                                             VALUES ('$receipt_id', '$service_id', '$quantity')";
                            if (!mysqli_query($conn, $service_insert)) {
                                throw new Exception("Error inserting service association: " . mysqli_error($conn));
                            }
                        }
                    }

                    mysqli_commit($conn);
                    $message = "Receipt created successfully! Total Amount: $" . number_format($total_amount, 2);

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
                    foreach ($selected_services as $service_data) {
                        $service_id = $service_data['service_id'];
                        $quantity = $service_data['quantity'];

                        // Verify service exists before inserting
                        $verify_service = "SELECT service_type_id FROM service_type WHERE service_type_id = '$service_id'";
                        $verify_result = mysqli_query($conn, $verify_service);

                        if (mysqli_num_rows($verify_result) > 0) {
                            $service_insert = "INSERT INTO receipt_service_type (receipt_id, service_type_id, quantity) 
                                             VALUES ('$receipt_id', '$service_id', '$quantity')";
                            if (!mysqli_query($conn, $service_insert)) {
                                throw new Exception("Error updating service association: " . mysqli_error($conn));
                            }
                        }
                    }

                    mysqli_commit($conn);
                    $message = "Receipt updated successfully! Total Amount: $" . number_format($total_amount, 2);

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
$patients_query = "SELECT patient_id, first_name, last_name FROM patient ORDER BY first_name";
$patients_result = mysqli_query($conn, $patients_query);

// Fetch staff for dropdown
$staff_query = "SELECT staff_id, first_name, last_name FROM staff ORDER BY first_name";
$staff_result = mysqli_query($conn, $staff_query);

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
    </style>
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
                <li><a href="#">Dashboard</a></li>
                <li><a href="patient_management.php">Patients</a></li>
                <li><a href="#">Staff</a></li>
                <li><a href="appointment_management.php">Appointments</a></li>
                <li><a href="service_type_managment.php">Services</a></li>
                <li><a href="disease_management.php">Diseases</a></li>
                <li><a href="checkup_management.php">General Checkups</a></li>
                <li><a href="treatment_management.php">Treatments</a></li>
                <li><a href="eyes_check_management.php">Eyes Check</a></li>
                <li class="active"><a href="receipt.php">Receipts</a></li>
                <li><a href="#">Reports</a></li>
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
                        <label for="receiptDate">Receipt Date</label>
                        <input type="date" id="receiptDate" name="receipt_date" required>
                    </div>
                    <div class="form-group">
                        <label for="remark">Remark</label>
                        <textarea id="remark" name="remark" rows="2" placeholder="Enter any additional notes..."></textarea>
                    </div>
                    <div class="form-group services-group">
                        <label>Services & Quantities</label>
                        <div class="services-container">
                            <?php
                            mysqli_data_seek($services_result, 0);
                            while ($service = mysqli_fetch_assoc($services_result)): ?>
                                <div class="service-checkbox">
                                    <input type="checkbox"
                                        id="service_<?php echo $service['service_type_id']; ?>"
                                        name="services[]"
                                        value="<?php echo $service['service_type_id']; ?>"
                                        onchange="updateTotalAmount()">
                                    <label for="service_<?php echo $service['service_type_id']; ?>">
                                        <?php echo htmlspecialchars($service['service_name']); ?>
                                        <span class="service-price">($<?php echo number_format($service['service_fee'], 2); ?>)</span>
                                    </label>
                                    <input type="number"
                                        class="quantity-input"
                                        name="quantity_<?php echo $service['service_type_id']; ?>"
                                        min="1"
                                        value="1"
                                        onchange="updateTotalAmount()"
                                        data-price="<?php echo $service['service_fee']; ?>">
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Total Amount</label>
                        <div class="total-amount-display" id="totalAmountDisplay">$0.00</div>
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
                            $service_query = "SELECT st.service_name, rst.quantity, st.service_fee 
                                            FROM receipt_service_type rst 
                                            JOIN service_type st ON rst.service_type_id = st.service_type_id 
                                            WHERE rst.receipt_id = '" . $receipt['receipt_id'] . "'";
                            $service_result = mysqli_query($conn, $service_query);
                            $services = [];
                            while ($service_row = mysqli_fetch_assoc($service_result)) {
                                $services[] = $service_row['service_name'] . ' (x' . $service_row['quantity'] . ')';
                            }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($receipt['receipt_id']); ?></td>
                                <td><?php echo htmlspecialchars($receipt['first_name'] . ' ' . $receipt['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($receipt['staff_first_name'] . ' ' . $receipt['staff_last_name']); ?></td>
                                <td><?php echo htmlspecialchars($receipt['date']); ?></td>
                                <td><strong>$<?php echo number_format($receipt['total_amount'], 2); ?></strong></td>
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
            const serviceCheckboxes = document.querySelectorAll('input[name="services[]"]');

            serviceCheckboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    const serviceId = checkbox.value;
                    const quantityInput = document.querySelector(`input[name="quantity_${serviceId}"]`);
                    const price = parseFloat(quantityInput.getAttribute('data-price'));
                    const quantity = parseInt(quantityInput.value) || 1;
                    total += (price * quantity);
                }
            });

            document.getElementById('totalAmountDisplay').textContent = '$' + total.toFixed(2);
        }

        function fillForm(receiptId, patientId, staffId, date, remark) {
            document.getElementById('receiptID').value = receiptId;
            document.getElementById('patientSelect').value = patientId;
            document.getElementById('staffSelect').value = staffId;
            document.getElementById('receiptDate').value = date;
            document.getElementById('remark').value = remark;

            // Load associated services
            loadReceiptServices(receiptId);
        }

        function loadReceiptServices(receiptId) {
            // Clear all checkboxes and reset quantities first
            document.querySelectorAll('input[name="services[]"]').forEach(checkbox => {
                checkbox.checked = false;
            });
            document.querySelectorAll('.quantity-input').forEach(input => {
                input.value = 1;
            });

            // Fetch and check associated services via AJAX
            fetch('get_receipt_services.php?receipt_id=' + encodeURIComponent(receiptId))
                .then(response => response.json())
                .then(services => {
                    services.forEach(service => {
                        const checkbox = document.getElementById('service_' + service.service_type_id);
                        const quantityInput = document.querySelector(`input[name="quantity_${service.service_type_id}"]`);
                        if (checkbox && quantityInput) {
                            checkbox.checked = true;
                            quantityInput.value = service.quantity;
                        }
                    });
                    updateTotalAmount();
                })
                .catch(error => {
                    console.error('Error loading receipt services:', error);
                });
        }

        function clearForm() {
            document.getElementById('receiptID').value = '';
            document.getElementById('patientSelect').selectedIndex = 0;
            document.getElementById('staffSelect').selectedIndex = 0;
            document.getElementById('receiptDate').value = '';
            document.getElementById('remark').value = '';

            // Clear all service checkboxes and reset quantities
            document.querySelectorAll('input[name="services[]"]').forEach(checkbox => {
                checkbox.checked = false;
            });
            document.querySelectorAll('.quantity-input').forEach(input => {
                input.value = 1;
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
    </script>
</body>

</html>