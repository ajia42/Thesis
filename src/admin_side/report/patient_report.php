<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: signin_admin.php');
    exit();
}

// Include database configuration
include("../../db_config.php");

// Query to get total patients count
$total_patients_query = "SELECT COUNT(*) AS total FROM patient";
$total_patients_result = mysqli_query($conn, $total_patients_query);
$total_patients = mysqli_fetch_assoc($total_patients_result)['total'];

// Query to get count by gender
$gender_count_query = "SELECT gender, COUNT(*) AS count FROM patient GROUP BY gender";
$gender_count_result = mysqli_query($conn, $gender_count_query);
$gender_counts = [];
while ($row = mysqli_fetch_assoc($gender_count_result)) {
    $gender_counts[$row['gender']] = $row['count'];
}

// Query to get all patients for the table
$patients_query = "SELECT * FROM patient";
$patients_result = mysqli_query($conn, $patients_query);
$patients = [];
while ($row = mysqli_fetch_assoc($patients_result)) {
    $patients[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Report</title>
    <link rel="stylesheet" href="../patient_management.css">
</head>
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

    .report-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .report-table th,
    .report-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    .report-table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }

    /* Scrollable table container */
    .table-container {
        max-height: 500px;
        overflow-y: auto;
        margin-top: 20px;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .patient-table {
        width: 100%;
        border-collapse: collapse;
    }

    .patient-table th,
    .patient-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    .patient-table th {
        background-color: #f8f9fa;
        font-weight: 600;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .patient-table tr:hover {
        background-color: #f5f5f5;
    }

    .table-header {
        margin-top: 30px;
        margin-bottom: 10px;
        font-size: 1.2rem;
        color: #2c3e50;
    }

    /* Custom scrollbar styling */
    .table-container::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .table-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .table-container::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }

    .table-container::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

    /* Details link styling */
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
</style>

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
                        <li><a href="patient_report.php">Patient Report</a></li>
                        <li><a href="staff_report.php">Staff Report</a></li>
                        <li><a href="income_report.php">Income Report</a></li>
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
                <h1>Patient Report</h1>
            </div>

            <div class="report-container">
                <div class="report-card">
                    <h3>Total Patients</h3>
                    <div class="report-value"><?php echo $total_patients; ?></div>
                </div>

                <div class="report-card">
                    <h3>Male Patients</h3>
                    <div class="report-value"><?php echo $gender_counts['Male'] ?? 0; ?></div>
                </div>

                <div class="report-card">
                    <h3>Female Patients</h3>
                    <div class="report-value"><?php echo $gender_counts['Female'] ?? 0; ?></div>
                </div>
            </div>

            <!-- Gender distribution table -->
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Gender</th>
                        <th>Count</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($gender_counts as $gender => $count): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($gender); ?></td>
                            <td><?php echo $count; ?></td>
                            <td><?php echo round(($count / $total_patients) * 100, 2); ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Patient list table with scrollable container -->
            <h2 class="table-header">Patient List</h2>
            <div class="table-container">
                <table class="patient-table">
                    <thead>
                        <tr>
                            <th>PATIENT ID</th>
                            <th>FIRST NAME</th>
                            <th>LAST NAME</th>
                            <th>GENDER</th>
                            <th>PHONE</th>
                            <th>DATE OF BIRTH</th>
                            <th>ADDRESS</th>
                            <th>DETAILS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($patients as $patient): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($patient['patient_id']); ?></td>
                                <td><?php echo htmlspecialchars($patient['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($patient['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($patient['gender']); ?></td>
                                <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                                <td><?php echo htmlspecialchars($patient['dob']); ?></td>
                                <td><?php echo htmlspecialchars($patient['address']); ?></td>
                                <td>
                                    <a href="#" class="details-link" onclick="showPatientDetails(
                                        '<?php echo htmlspecialchars($patient['patient_id']); ?>',
                                        '<?php echo htmlspecialchars($patient['first_name']); ?>',
                                        '<?php echo htmlspecialchars($patient['last_name']); ?>',
                                        '<?php echo htmlspecialchars($patient['gender']); ?>',
                                        '<?php echo htmlspecialchars($patient['phone']); ?>',
                                        '<?php echo htmlspecialchars($patient['dob']); ?>',
                                        '<?php echo htmlspecialchars($patient['address']); ?>'
                                    )">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Patient Details Modal -->
    <div id="patientDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Patient Details</h2>
                <span class="close" onclick="closePatientDetailsModal()">&times;</span>
            </div>
            <div class="patient-details">
                <div class="detail-group">
                    <span class="detail-label">Patient ID</span>
                    <div class="detail-value" id="detail-patient-id"></div>
                </div>
                <div class="detail-group">
                    <span class="detail-label">First Name</span>
                    <div class="detail-value" id="detail-first-name"></div>
                </div>
                <div class="detail-group">
                    <span class="detail-label">Last Name</span>
                    <div class="detail-value" id="detail-last-name"></div>
                </div>
                <div class="detail-group">
                    <span class="detail-label">Gender</span>
                    <div class="detail-value" id="detail-gender"></div>
                </div>
                <div class="detail-group">
                    <span class="detail-label">Phone</span>
                    <div class="detail-value" id="detail-phone"></div>
                </div>
                <div class="detail-group">
                    <span class="detail-label">Date of Birth</span>
                    <div class="detail-value" id="detail-dob"></div>
                </div>
                <div class="detail-group" style="grid-column: span 2;">
                    <span class="detail-label">Address</span>
                    <div class="detail-value" id="detail-address"></div>
                </div>
            </div>
            <div class="modal-actions">
                <button onclick="closePatientDetailsModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        // Function to show patient details in modal
        function showPatientDetails(patientId, firstName, lastName, gender, phone, dob, address) {
            document.getElementById('detail-patient-id').textContent = patientId;
            document.getElementById('detail-first-name').textContent = firstName;
            document.getElementById('detail-last-name').textContent = lastName;
            document.getElementById('detail-gender').textContent = gender;
            document.getElementById('detail-phone').textContent = phone;
            document.getElementById('detail-dob').textContent = dob;
            document.getElementById('detail-address').textContent = address || 'Not specified';

            document.getElementById('patientDetailsModal').style.display = 'block';
        }

        // Function to close the patient details modal
        function closePatientDetailsModal() {
            document.getElementById('patientDetailsModal').style.display = 'none';
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            if (event.target == document.getElementById('patientDetailsModal')) {
                closePatientDetailsModal();
            }
        }

        function toggleSubmenu(element) {
            event.preventDefault();
            const parent = element.parentElement;
            parent.classList.toggle('active');
        }
    </script>
</body>

</html>