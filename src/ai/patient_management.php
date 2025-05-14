<?php
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

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $dob = mysqli_real_escape_string($conn, $_POST['dob']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    // Validation checks
    $errors = [];

    // Check email uniqueness
    $email_check = "SELECT * FROM patient WHERE email = '$email'";
    $email_result = mysqli_query($conn, $email_check);
    if (mysqli_num_rows($email_result) > 0) {
        $errors[] = "Email already exists.";
    }

    // Check phone uniqueness
    $phone_check = "SELECT * FROM patient WHERE phone = '$phone'";
    $phone_result = mysqli_query($conn, $phone_check);
    if (mysqli_num_rows($phone_result) > 0) {
        $errors[] = "Phone number already exists.";
    }

    // Action based on button click
    if (isset($_POST['save_button']) && empty($errors)) {
        // Generate new patient ID
        $patient_id = generatePatientID($conn);

        // Prepare INSERT query
        $insert_query = "INSERT INTO patient (patient_id, first_name, last_name, gender, email, phone, dob, address) 
                         VALUES ('$patient_id', '$first_name', '$last_name', '$gender', '$email', '$phone', '$dob', '$address')";

        if (mysqli_query($conn, $insert_query)) {
            echo "<script>alert('Patient added successfully!');</script>";
        } else {
            echo "<script>alert('Error adding patient: " . mysqli_error($conn) . "');</script>";
        }
    }

    // Update functionality
    if (isset($_POST['update_button']) && !empty($_POST['patient_id'])) {
        $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);

        // Prepare UPDATE query
        $update_query = "UPDATE patient 
                         SET first_name = '$first_name', 
                             last_name = '$last_name', 
                             gender = '$gender', 
                             email = '$email', 
                             phone = '$phone', 
                             dob = '$dob', 
                             address = '$address' 
                         WHERE patient_id = '$patient_id'";

        if (mysqli_query($conn, $update_query)) {
            echo "<script>alert('Patient updated successfully!');</script>";
        } else {
            echo "<script>alert('Error updating patient: " . mysqli_error($conn) . "');</script>";
        }
    }

    // Delete functionality
    if (isset($_POST['delete_button']) && !empty($_POST['patient_id'])) {
        $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);

        // Prepare DELETE query
        $delete_query = "DELETE FROM patient WHERE patient_id = '$patient_id'";

        if (mysqli_query($conn, $delete_query)) {
            echo "<script>alert('Patient deleted successfully!');</script>";
        } else {
            echo "<script>alert('Error deleting patient: " . mysqli_error($conn) . "');</script>";
        }
    }
}

// Search functionality
$search_query = "";
$search_results = [];
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = mysqli_real_escape_string($conn, $_GET['search']);
    $search_query = "SELECT * FROM patient 
                     WHERE patient_id LIKE '%$search_term%' 
                     OR first_name LIKE '%$search_term%' 
                     OR last_name LIKE '%$search_term%' 
                     OR email LIKE '%$search_term%'
                     OR phone LIKE '%$search_term%'";
    $search_result = mysqli_query($conn, $search_query);

    if ($search_result) {
        while ($row = mysqli_fetch_assoc($search_result)) {
            $search_results[] = $row;
        }
    }
}

// Fetch all patients if no search is performed
if (empty($search_results)) {
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

                <li><a href="#">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="3" y1="9" x2="21" y2="9"></line>
                            <line x1="9" y1="21" x2="9" y2="9"></line>
                        </svg>
                        Dashboard</a></li>

                <li><a href="#">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        Patients</a></li>

                <li><a href="#">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        Staff</a></li>

                <li><a href="#">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        Appointments</a></li>

                <li><a href="#">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                        Services</a></li>

                <li><a href="#">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                        Diseases</a></li>

                <li><a href="#">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20.42 4.58a5.4 5.4 0 0 0-7.65 0l-.77.78-.77-.78a5.4 5.4 0 0 0-7.65 0C1.46 6.7 1.33 10.28 4 13l8 8 8-8c2.67-2.72 2.54-6.3.42-8.42z"></path>
                        </svg>
                        General Checkups</a></li>

                <li><a href="#">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                        </svg>
                        Treatments</a></li>

                <li><a href="#">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                        Receipts</a></li>

                <li><a href="#">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="20" x2="18" y2="10"></line>
                            <line x1="12" y1="20" x2="12" y2="4"></line>
                            <line x1="6" y1="20" x2="6" y2="14"></line>
                        </svg>
                        Reports</a></li>

                <li><a href="#">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                        </svg>
                        Settings</a>
                </li>
            </ul>
        </aside>
        <main class="main-content">
            <div class="header">
                <h1>Patient Management</h1>
                <button class="new-patient-button" name="new_patient" onclick="clearForm()">+ New Patient</button>
            </div>

            <!-- Patient Form -->
            <form method="POST" action="">
                <div class="patient-form">
                    <div class="form-group">
                        <label for="patientID">Patient ID</label>
                        <input type="text" id="patientID" name="patient_id" readonly>
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
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" required>
                    </div>
                    <div class="form-group">
                        <label for="dob">Date of Birth</label>
                        <input type="date" id="dob" name="dob" placeholder="dd/mm/yyyy" required>
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address">
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
                    <input type="search" name="search" placeholder="Search patients by name or email..."
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit">Search</button>
                </form>
            </div>

            <!-- Patient Table -->
            <table class="patient-table">
                <thead>
                    <tr>
                        <th>PATIENT ID</th>
                        <th>FIRST NAME</th>
                        <th>LAST NAME</th>
                        <th>GENDER</th>
                        <th>EMAIL</th>
                        <th>PHONE</th>
                        <th>DATE OF BIRTH</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($search_results as $patient): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($patient['patient_id']); ?></td>
                            <td><?php echo htmlspecialchars($patient['first_name']); ?></td>
                            <td><?php echo htmlspecialchars($patient['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($patient['gender']); ?></td>
                            <td><?php echo htmlspecialchars($patient['email']); ?></td>
                            <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                            <td><?php echo htmlspecialchars($patient['dob']); ?></td>
                            <td>
                                <a href="#" onclick="fillForm('<?php echo htmlspecialchars($patient['patient_id']); ?>', 
                                '<?php echo htmlspecialchars($patient['first_name']); ?>', 
                                '<?php echo htmlspecialchars($patient['last_name']); ?>', 
                                '<?php echo htmlspecialchars($patient['gender']); ?>', 
                                '<?php echo htmlspecialchars($patient['email']); ?>', 
                                '<?php echo htmlspecialchars($patient['phone']); ?>', 
                                '<?php echo htmlspecialchars($patient['dob']); ?>', 
                                '<?php echo htmlspecialchars($patient['address']); ?>')">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>

    <script>
        function fillForm(patientId, firstName, lastName, gender, email, phone, dob, address) {
            document.getElementById('patientID').value = patientId;
            document.getElementById('firstName').value = firstName;
            document.getElementById('lastName').value = lastName;
            document.getElementById('gender').value = gender;
            document.getElementById('email').value = email;
            document.getElementById('phone').value = phone;
            document.getElementById('dob').value = dob;
            document.getElementById('address').value = address;
        }

        function clearForm() {
            document.getElementById('patientID').value = ''; // Clear Patient ID
            document.getElementById('firstName').value = ''; // Clear First Name
            document.getElementById('lastName').value = ''; // Clear Last Name
            document.getElementById('gender').selectedIndex = 0; // Reset Gender to default
            document.getElementById('email').value = ''; // Clear Email
            document.getElementById('phone').value = ''; // Clear Phone
            document.getElementById('dob').value = ''; // Clear Date of Birth
            document.getElementById('address').value = ''; // Clear Address
            // Focus on first name input
            document.getElementById('firstName').focus();
        }

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>

</html>