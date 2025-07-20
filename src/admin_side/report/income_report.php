<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: signin_admin.php');
    exit();
}

// Include database configuration
include("../../db_config.php");

// Default date range (current month)
$start_date = date('Y-m-01');
$end_date = date('Y-m-d');

// Check if date range is submitted
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $start_date = mysqli_real_escape_string($conn, $_GET['start_date']);
    $end_date = mysqli_real_escape_string($conn, $_GET['end_date']);

    // Validate dates
    if ($start_date > $end_date) {
        $errors = "End date must be after start date";
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-d');
    }
}

// Query to get total income for the date range
$total_income_query = "SELECT SUM(total_amount) AS total_income 
                       FROM receipt 
                       WHERE date BETWEEN '$start_date' AND '$end_date'";
$total_income_result = mysqli_query($conn, $total_income_query);
$total_income = mysqli_fetch_assoc($total_income_result)['total_income'] ?? 0;

// Query to get count of receipts in the date range
$receipt_count_query = "SELECT COUNT(*) AS receipt_count 
                       FROM receipt 
                       WHERE date BETWEEN '$start_date' AND '$end_date'";
$receipt_count_result = mysqli_query($conn, $receipt_count_query);
$receipt_count = mysqli_fetch_assoc($receipt_count_result)['receipt_count'] ?? 0;

// Query to get all receipts in the date range with patient and staff info
$receipts_query = "SELECT r.*, p.first_name AS patient_first_name, p.last_name AS patient_last_name,
                   s.first_name AS staff_first_name, s.last_name AS staff_last_name
                   FROM receipt r
                   LEFT JOIN patient p ON r.patient_id = p.patient_id
                   LEFT JOIN staff s ON r.staff_id = s.staff_id
                   WHERE r.date BETWEEN '$start_date' AND '$end_date'
                   ORDER BY r.date DESC, r.receipt_id DESC";
$receipts_result = mysqli_query($conn, $receipts_query);
$receipts = [];
while ($row = mysqli_fetch_assoc($receipts_result)) {
    $receipts[] = $row;
}

// Function to get services for a receipt
function getReceiptServices($conn, $receipt_id)
{
    $services_query = "SELECT st.service_name, st.service_fee 
                      FROM receipt_service_type rst
                      JOIN service_type st ON rst.service_type_id = st.service_type_id
                      WHERE rst.receipt_id = '$receipt_id'";
    $services_result = mysqli_query($conn, $services_query);
    $services = [];
    while ($service = mysqli_fetch_assoc($services_result)) {
        $services[] = $service;
    }
    return $services;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Income Report</title>
    <link rel="stylesheet" href="../patient_management.css">
    <style>
        .report-container {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .report-card {
            flex: 1;
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .report-card h3 {
            margin-top: 0;
            color: #555;
        }

        .report-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .report-value.income {
            color: #10b981;
        }

        .date-range-form {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }

        .form-group input[type="date"] {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-actions {
            margin-top: 0;
        }

        .table-container {
            max-height: 500px;
            overflow-y: auto;
            margin-top: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .receipt-table {
            width: 100%;
            border-collapse: collapse;
        }

        .receipt-table th,
        .receipt-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .receipt-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .receipt-table tr:hover {
            background-color: #f5f5f5;
        }

        .amount-cell {
            font-weight: bold;
            color: #10b981;
        }

        .details-link {
            color: #1976d2;
            text-decoration: none;
            cursor: pointer;
        }

        .details-link:hover {
            text-decoration: underline;
        }

        /* Modal styling */
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

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .modal-title {
            font-size: 1.5rem;
            color: #2c3e50;
            margin: 0;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #333;
        }

        .receipt-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .detail-group {
            margin-bottom: 15px;
        }

        .detail-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 5px;
            display: block;
        }

        .detail-value {
            padding: 8px;
            background-color: #f9f9f9;
            border-radius: 4px;
            border: 1px solid #eee;
        }

        .services-list {
            grid-column: span 2;
        }

        .service-item {
            display: flex;
            justify-content: space-between;
            padding: 8px;
            border-bottom: 1px solid #eee;
        }

        .service-name {
            flex: 2;
        }

        .service-price {
            flex: 1;
            text-align: right;
            font-weight: bold;
            color: #10b981;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .modal-actions button {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            background-color: #1976d2;
            color: white;
            cursor: pointer;
        }

        .modal-actions button:hover {
            background-color: #1565c0;
        }

        .error {
            color: #ef4444;
            background-color: #fee2e2;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <aside class="sidebar">
            <!-- Sidebar content remains the same as in patient_report.php -->
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
                    <a href="../admin_profile.php">
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

                <li><a href="../patient_management.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        Patients</a></li>

                <li><a href="../reception_management.php">
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

                <li><a href="../staff_management.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        Staff</a></li>

                <li><a href="../appointment_management.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        Appointments</a></li>

                <li><a href="../service_type_managment.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                        Services</a></li>

                <li><a href="../disease_management.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                        Diseases</a></li>

                <li><a href="../checkup_management.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20.42 4.58a5.4 5.4 0 0 0-7.65 0l-.77.78-.77-.78a5.4 5.4 0 0 0-7.65 0C1.46 6.7 1.33 10.28 4 13l8 8 8-8c2.67-2.72 2.54-6.3.42-8.42z"></path>
                        </svg>
                        General Checkups</a></li>

                <li><a href="../treatment_management.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                        </svg>
                        Treatments</a></li>

                <li><a href="../eyes_check_management.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        Eyes Check</a>

                <li><a href="../receipt.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                        Receipts</a></li>

                <li class="has-submenu active">
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
                        <li><a href="patient_report.php">Patient Report</a></li>
                        <li><a href="staff_report.php">Staff Report</a></li>
                        <li><a href="income_report.php">Income Report</a></li>
                    </ul>
                </li>

                <li><a href="../logout.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-out-icon lucide-log-out">
                            <path d="m16 17 5-5-5-5" />
                            <path d="M21 12H9" />
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                        </svg>
                        Log out</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>Income Report</h1>
            </div>

            <?php if (isset($errors)): ?>
                <div class="error"><?php echo $errors; ?></div>
            <?php endif; ?>

            <!-- Date Range Form -->
            <form method="GET" action="" class="date-range-form">
                <div class="form-group">
                    <label for="start_date">From Date</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
                </div>
                <div class="form-group">
                    <label for="end_date">To Date</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
                </div>
                <div class="form-actions">
                    <button type="submit">Generate Report</button>
                </div>
            </form>

            <!-- Summary Cards -->
            <div class="report-container">
                <div class="report-card">
                    <h3>Total Income</h3>
                    <div class="report-value income"><?php echo number_format($total_income, 0); ?> LAK</div>
                </div>
                <div class="report-card">
                    <h3>Number of Receipts</h3>
                    <div class="report-value"><?php echo $receipt_count; ?></div>
                </div>
                <div class="report-card">
                    <h3>Date Range</h3>
                    <div class="report-value">
                        <?php echo date('d/m/Y', strtotime($start_date)); ?> -
                        <?php echo date('d/m/Y', strtotime($end_date)); ?>
                    </div>
                </div>
            </div>

            <!-- Receipts Table -->
            <h2>Receipts</h2>
            <div class="table-container">
                <table class="receipt-table">
                    <thead>
                        <tr>
                            <th>RECEIPT ID</th>
                            <th>DATE</th>
                            <th>PATIENT</th>
                            <th>STAFF</th>
                            <th>AMOUNT</th>
                            <th>REMARK</th>
                            <th>DETAILS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($receipts as $receipt): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($receipt['receipt_id']); ?></td>
                                <td><?php echo htmlspecialchars($receipt['date']); ?></td>
                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        ($receipt['patient_first_name'] ?? 'N/A') . ' ' .
                                            ($receipt['patient_last_name'] ?? '')
                                    );
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        ($receipt['staff_first_name'] ?? 'N/A') . ' ' .
                                            ($receipt['staff_last_name'] ?? '')
                                    );
                                    ?>
                                </td>
                                <td class="amount-cell"><?php echo number_format($receipt['total_amount'], 0); ?> LAK</td>
                                <td><?php echo htmlspecialchars(substr($receipt['remark'], 0, 20) . (strlen($receipt['remark']) > 20 ? '...' : '')); ?></td>
                                <td>
                                    <a href="#" class="details-link" onclick="showReceiptDetails(
                                        '<?php echo htmlspecialchars($receipt['receipt_id']); ?>',
                                        '<?php echo htmlspecialchars($receipt['date']); ?>',
                                        '<?php echo htmlspecialchars($receipt['patient_first_name'] . ' ' . $receipt['patient_last_name']); ?>',
                                        '<?php echo htmlspecialchars($receipt['staff_first_name'] . ' ' . $receipt['staff_last_name']); ?>',
                                        '<?php echo number_format($receipt['total_amount'], 0); ?>',
                                        '<?php echo htmlspecialchars($receipt['remark']); ?>'
                                    )">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($receipts)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">No receipts found for the selected date range</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Receipt Details Modal -->
    <div id="receiptDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Receipt Details</h2>
                <span class="close" onclick="closeReceiptDetailsModal()">&times;</span>
            </div>
            <div class="receipt-details">
                <div class="detail-group">
                    <span class="detail-label">Receipt ID</span>
                    <div class="detail-value" id="detail-receipt-id"></div>
                </div>
                <div class="detail-group">
                    <span class="detail-label">Date</span>
                    <div class="detail-value" id="detail-date"></div>
                </div>
                <div class="detail-group">
                    <span class="detail-label">Patient</span>
                    <div class="detail-value" id="detail-patient"></div>
                </div>
                <div class="detail-group">
                    <span class="detail-label">Staff</span>
                    <div class="detail-value" id="detail-staff"></div>
                </div>
                <div class="detail-group">
                    <span class="detail-label">Total Amount</span>
                    <div class="detail-value" id="detail-amount"></div>
                </div>
                <div class="detail-group" style="grid-column: span 2;">
                    <span class="detail-label">Remark</span>
                    <div class="detail-value" id="detail-remark"></div>
                </div>
                <div class="services-list">
                    <span class="detail-label">Services</span>
                    <div id="services-container"></div>
                </div>
            </div>
            <div class="modal-actions">
                <button onclick="closeReceiptDetailsModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        // Function to show receipt details in modal
        function showReceiptDetails(receiptId, date, patient, staff, amount, remark) {
            document.getElementById('detail-receipt-id').textContent = receiptId;
            document.getElementById('detail-date').textContent = date;
            document.getElementById('detail-patient').textContent = patient;
            document.getElementById('detail-staff').textContent = staff;
            document.getElementById('detail-amount').textContent = amount + ' LAK';
            document.getElementById('detail-remark').textContent = remark || 'No remark';

            // Clear previous services
            document.getElementById('services-container').innerHTML = '';

            // Fetch services via AJAX
            fetch('../get_receipt_services.php?receipt_id=' + encodeURIComponent(receiptId))
                .then(response => response.json())
                .then(services => {
                    if (services.length > 0) {
                        // Fetch service details for each service
                        services.forEach(service => {
                            fetch('get_service_details.php?service_id=' + encodeURIComponent(service.service_type_id))
                                .then(response => response.json())
                                .then(serviceDetails => {
                                    const serviceItem = document.createElement('div');
                                    serviceItem.className = 'service-item';
                                    serviceItem.innerHTML = `
                                        <span class="service-name">${serviceDetails.service_name}</span>
                                        <span class="service-price">${parseInt(serviceDetails.service_fee).toLocaleString()} LAK</span>
                                    `;
                                    document.getElementById('services-container').appendChild(serviceItem);
                                })
                                .catch(error => {
                                    console.error('Error fetching service details:', error);
                                });
                        });
                    } else {
                        document.getElementById('services-container').textContent = 'No services found';
                    }
                })
                .catch(error => {
                    console.error('Error fetching receipt services:', error);
                    document.getElementById('services-container').textContent = 'Error loading services';
                });

            document.getElementById('receiptDetailsModal').style.display = 'block';
        }

        // Function to close the receipt details modal
        function closeReceiptDetailsModal() {
            document.getElementById('receiptDetailsModal').style.display = 'none';
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            if (event.target == document.getElementById('receiptDetailsModal')) {
                closeReceiptDetailsModal();
            }
        }

        function toggleSubmenu(element) {
            event.preventDefault();
            const parent = element.parentElement;
            parent.classList.toggle('active');
        }

        // Set max date to today for end date
        document.addEventListener("DOMContentLoaded", function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('end_date').setAttribute('max', today);
        });
    </script>
</body>

</html>