<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../signin_admin.php');
    exit();
}

// Include database configuration
include("../../db_config.php");

// Default date range (all time)
$start_date = '';
$end_date = '';

// Check if date range is submitted
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $start_date = mysqli_real_escape_string($conn, $_GET['start_date']);
    $end_date = mysqli_real_escape_string($conn, $_GET['end_date']);

    // Validate dates
    if (!empty($start_date) && !empty($end_date) && $start_date > $end_date) {
        $errors = "End date must be after start date";
        $start_date = '';
        $end_date = '';
    }
}

// Build date condition for queries
$date_condition = "";
if (!empty($start_date) && !empty($end_date)) {
    $date_condition = "AND t.date BETWEEN '$start_date' AND '$end_date'";
}

// Query to get disease statistics
$disease_stats_query = "SELECT d.disease_id, d.disease_name, COUNT(td.treatment_id) AS patient_count
                       FROM disease d
                       LEFT JOIN treatment_disease td ON d.disease_id = td.disease_id
                       LEFT JOIN treatment t ON td.treatment_id = t.treatment_id
                       WHERE 1=1 $date_condition
                       GROUP BY d.disease_id, d.disease_name
                       ORDER BY patient_count DESC";
$disease_stats_result = mysqli_query($conn, $disease_stats_query);
$disease_stats = [];
while ($row = mysqli_fetch_assoc($disease_stats_result)) {
    $disease_stats[] = $row;
}

// Query to get total patients with diseases
$total_patients_query = "SELECT COUNT(DISTINCT t.patient_id) AS total
                        FROM treatment t
                        JOIN treatment_disease td ON t.treatment_id = td.treatment_id
                        WHERE 1=1 $date_condition";
$total_patients_result = mysqli_query($conn, $total_patients_query);
$total_patients = mysqli_fetch_assoc($total_patients_result)['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disease Report</title>
    <link rel="stylesheet" href="../patient_management.css">
    <link rel="icon" href="../../images/logo.svg" type="image/svg+xml">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Phetsarath:wght@400;700&display=swap" rel="stylesheet">
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

        .disease-table {
            width: 100%;
            border-collapse: collapse;
        }

        .disease-table th,
        .disease-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .disease-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .disease-table tr:hover {
            background-color: #f5f5f5;
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

        .patient-details {
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

        .patients-list {
            grid-column: span 2;
        }

        .patient-item {
            display: flex;
            justify-content: space-between;
            padding: 8px;
            border-bottom: 1px solid #eee;
        }

        .patient-name {
            flex: 2;
        }

        .treatment-date {
            flex: 1;
            text-align: right;
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
                        <li <?php echo basename($_SERVER['PHP_SELF']) == '../patient_management.php' ? 'class="active"' : ''; ?>><a href="../patient_management.php">ຂໍ້ມູນຄົນເຈັບ</a></li>
                        <li <?php echo basename($_SERVER['PHP_SELF']) == '../service_type_managment.php' ? 'class="active"' : ''; ?>><a href="../service_type_managment.php">ຂໍ້ມູນປະເພດບໍລິການ</a></li>
                        <li <?php echo basename($_SERVER['PHP_SELF']) == '../disease_management.php' ? 'class="active"' : ''; ?>><a href="../disease_management.php">ຂໍ້ມູນພະຍາດ</a></li>
                        <li <?php echo basename($_SERVER['PHP_SELF']) == '../staff_management.php' ? 'class="active"' : ''; ?>><a href="../staff_management.php">ຂໍ້ມູນພະນັກງານ</a></li>
                    </ul>
                </li>

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
                        ຕ້ອນຮັບ</a></li>

                <li><a href="../appointment_management.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        ຈັດການຈອງຄິວ</a></li>

                <li><a href="../checkup_management.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20.42 4.58a5.4 5.4 0 0 0-7.65 0l-.77.78-.77-.78a5.4 5.4 0 0 0-7.65 0C1.46 6.7 1.33 10.28 4 13l8 8 8-8c2.67-2.72 2.54-6.3.42-8.42z"></path>
                        </svg>
                        ກວດເບື້ອງຕົ້ນ</a></li>

                <li><a href="../treatment_management.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                        </svg>
                        ກວດຮັກສາ</a></li>

                <li><a href="../eyes_check_management.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        ວັດແທກສາຍຕາ</a>

                <li><a href="../receipt.php">
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
                        <li <?php echo basename($_SERVER['PHP_SELF']) == 'patient_report.php' ? 'class="active"' : ''; ?>><a href="patient_report.php">ລາຍງານຄົນເຈັບ</a></li>
                        <li <?php echo basename($_SERVER['PHP_SELF']) == 'staff_report.php' ? 'class="active"' : ''; ?>><a href="staff_report.php">ລາຍງານພະນັກງານ</a></li>
                        <li <?php echo basename($_SERVER['PHP_SELF']) == 'income_report.php' ? 'class="active"' : ''; ?>><a href="income_report.php">ລາຍງານລາຍຮັບ</a></li>
                        <li <?php echo basename($_SERVER['PHP_SELF']) == 'disease_report.php' ? 'class="active"' : ''; ?>><a href="disease_report.php">ລາຍງານພະຍາດ</a></li>
                    </ul>
                </li>

                <li><a href="logout.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-out-icon lucide-log-out">
                            <path d="m16 17 5-5-5-5" />
                            <path d="M21 12H9" />
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                        </svg>
                        ອອກຈາກລະບົບ</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>Disease Report</h1>
            </div>

            <?php if (isset($errors)): ?>
                <div class="error"><?php echo $errors; ?></div>
            <?php endif; ?>

            <!-- Date Range Form -->
            <form method="GET" action="" class="date-range-form">
                <div class="form-group">
                    <label for="start_date">From Date</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                </div>
                <div class="form-group">
                    <label for="end_date">To Date</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                </div>
                <div class="form-actions">
                    <button type="submit">Generate Report</button>
                    <button type="button" onclick="window.location.href='disease_report.php'">Reset</button>
                </div>
            </form>

            <!-- Summary Cards -->
            <div class="report-container">
                <div class="report-card">
                    <h3>Total Patients with Diseases</h3>
                    <div class="report-value"><?php echo $total_patients; ?></div>
                </div>
                <div class="report-card">
                    <h3>Date Range</h3>
                    <div class="report-value">
                        <?php echo !empty($start_date) ? date('d/m/Y', strtotime($start_date)) : 'All time'; ?> -
                        <?php echo !empty($end_date) ? date('d/m/Y', strtotime($end_date)) : 'Present'; ?>
                    </div>
                </div>
            </div>

            <!-- Disease Statistics Table -->
            <h2>Disease Statistics</h2>
            <div class="table-container">
                <table class="disease-table">
                    <thead>
                        <tr>
                            <th>DISEASE ID</th>
                            <th>DISEASE NAME</th>
                            <th>PATIENT COUNT</th>
                            <th>PERCENTAGE</th>
                            <th>DETAILS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($disease_stats as $disease): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($disease['disease_id']); ?></td>
                                <td><?php echo htmlspecialchars($disease['disease_name']); ?></td>
                                <td><?php echo $disease['patient_count']; ?></td>
                                <td><?php echo $total_patients > 0 ? round(($disease['patient_count'] / $total_patients) * 100, 2) : 0; ?>%</td>
                                <td>
                                    <a href="#" class="details-link" onclick="showDiseaseDetails(
                                        '<?php echo htmlspecialchars($disease['disease_id']); ?>',
                                        '<?php echo htmlspecialchars($disease['disease_name']); ?>',
                                        '<?php echo $disease['patient_count']; ?>',
                                        '<?php echo !empty($start_date) ? $start_date : ''; ?>',
                                        '<?php echo !empty($end_date) ? $end_date : ''; ?>'
                                    )">View Patients</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($disease_stats)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">No disease data found for the selected date range</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Disease Details Modal -->
    <div id="diseaseDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Patient List for <span id="modal-disease-name"></span></h2>
                <span class="close" onclick="closeDiseaseDetailsModal()">&times;</span>
            </div>
            <div class="patient-details">
                <div class="detail-group">
                    <span class="detail-label">Disease ID</span>
                    <div class="detail-value" id="detail-disease-id"></div>
                </div>
                <div class="detail-group">
                    <span class="detail-label">Disease Name</span>
                    <div class="detail-value" id="detail-disease-name"></div>
                </div>
                <div class="detail-group">
                    <span class="detail-label">Patient Count</span>
                    <div class="detail-value" id="detail-patient-count"></div>
                </div>
                <div class="detail-group">
                    <span class="detail-label">Date Range</span>
                    <div class="detail-value" id="detail-date-range"></div>
                </div>
                <div class="patients-list">
                    <span class="detail-label">Patients</span>
                    <div id="patients-container"></div>
                </div>
            </div>
            <div class="modal-actions">
                <button onclick="closeDiseaseDetailsModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        // Function to show disease details in modal
        function showDiseaseDetails(diseaseId, diseaseName, patientCount, startDate, endDate) {
            document.getElementById('detail-disease-id').textContent = diseaseId;
            document.getElementById('detail-disease-name').textContent = diseaseName;
            document.getElementById('modal-disease-name').textContent = diseaseName;
            document.getElementById('detail-patient-count').textContent = patientCount;

            // Set date range display
            let dateRangeText = 'All time';
            if (startDate && endDate) {
                dateRangeText = formatDate(startDate) + ' to ' + formatDate(endDate);
            } else if (startDate) {
                dateRangeText = 'From ' + formatDate(startDate);
            } else if (endDate) {
                dateRangeText = 'Until ' + formatDate(endDate);
            }
            document.getElementById('detail-date-range').textContent = dateRangeText;

            // Clear previous patients
            document.getElementById('patients-container').innerHTML = '';

            // Fetch patients via AJAX
            fetch('get_disease_patients.php?disease_id=' + encodeURIComponent(diseaseId) +
                    '&start_date=' + encodeURIComponent(startDate) +
                    '&end_date=' + encodeURIComponent(endDate))
                .then(response => response.json())
                .then(patients => {
                    if (patients.length > 0) {
                        patients.forEach(patient => {
                            const patientItem = document.createElement('div');
                            patientItem.className = 'patient-item';
                            patientItem.innerHTML = `
                                <span class="patient-name">${patient.first_name} ${patient.last_name}</span>
                                <span class="treatment-date">${formatDate(patient.treatment_date)}</span>
                            `;
                            document.getElementById('patients-container').appendChild(patientItem);
                        });
                    } else {
                        document.getElementById('patients-container').textContent = 'No patients found';
                    }
                })
                .catch(error => {
                    console.error('Error fetching disease patients:', error);
                    document.getElementById('patients-container').textContent = 'Error loading patients';
                });

            document.getElementById('diseaseDetailsModal').style.display = 'block';
        }

        // Function to format date
        function formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString();
        }

        // Function to close the disease details modal
        function closeDiseaseDetailsModal() {
            document.getElementById('diseaseDetailsModal').style.display = 'none';
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            if (event.target == document.getElementById('diseaseDetailsModal')) {
                closeDiseaseDetailsModal();
            }
        }

        // Set max date to today for end date
        document.addEventListener("DOMContentLoaded", function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('end_date').setAttribute('max', today);
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