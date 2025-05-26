<?php

session_start();
if (!isset($_SESSION['staff_id'])) {
    header('Location: signin_staff.php');
    exit();
}

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

// Validation checks
$validate_email = "";
$validate_phone = "";
$errors = '';
$message = '';

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


    // Action based on button click
    if (isset($_POST['save_button'])) {

        // Check email uniqueness
        $email_check = "SELECT * FROM patient WHERE email = '$email'";
        $email_result = mysqli_query($conn, $email_check);
        if (mysqli_num_rows($email_result) > 0) {
            $errors = "Email already exists.";
        }

        // Check phone uniqueness
        $phone_check = "SELECT * FROM patient WHERE phone = '$phone'";
        $phone_result = mysqli_query($conn, $phone_check);
        if (mysqli_num_rows($phone_result) > 0) {
            $errors = "Phone number already exists.";
        }

        if (empty($errors)) {
            // Generate new patient ID
            $patient_id = generatePatientID($conn);

            // Prepare INSERT query
            $insert_query = "INSERT INTO patient (patient_id, first_name, last_name, gender, email, phone, dob, address) 
                         VALUES ('$patient_id', '$first_name', '$last_name', '$gender', '$email', '$phone', '$dob', '$address')";

            if (mysqli_query($conn, $insert_query)) {
                $message = "Patient added successfully!";
                // echo "<script>alert('Patient added successfully!');</script>";
                $patient_id = $first_name = $last_name = $gender = $email = $phone = $dob = $address = '';
            } else {
                // echo "<script>alert('Error adding patient: " . mysqli_error($conn) . "');</script>";
                $errors = "Error adding patient: " . mysqli_error($conn);
            }
        }
    }

    // Update functionality
    if (isset($_POST['update_button']) && !empty($_POST['patient_id'])) {
        $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);

        // Check if email exists but exclude the current patient
        $email_check = "SELECT * FROM patient WHERE email = '$email' AND patient_id != '$patient_id'";
        $email_result = mysqli_query($conn, $email_check);
        if (mysqli_num_rows($email_result) > 0) {
            $errors = "Email already exists.";
        }

        // Check if phone exists but exclude the current patient
        $phone_check = "SELECT * FROM patient WHERE phone = '$phone' AND patient_id != '$patient_id'";
        $phone_result = mysqli_query($conn, $phone_check);
        if (mysqli_num_rows($phone_result) > 0) {
            $errors = "Phone number already exists.";
        }

        if (empty($errors)) {
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
                // echo "<script>alert('Patient updated successfully!');</script>";
                $message = "Patient updated successfully!";
                $patient_id = $first_name = $last_name = $gender = $email = $phone = $dob = $address = '';
            } else {
                // echo "<script>alert('Error updating patient: " . mysqli_error($conn) . "');</script>";
                $errors = "Error adding patient: " . mysqli_error($conn);
            }
        }
    }

    // Delete functionality
    if (isset($_POST['delete_button']) && !empty($_POST['patient_id'])) {
        $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);

        // Prepare DELETE query
        $delete_query = "DELETE FROM patient WHERE patient_id = '$patient_id'";

        if (mysqli_query($conn, $delete_query)) {
            // echo "<script>alert('Patient deleted successfully!');</script>";
            $message = "Patient deleted successfully!";
        } else {
            // echo "<script>alert('Error deleting patient: " . mysqli_error($conn) . "');</script>";
            $errors = "Error adding patient: " . mysqli_error($conn);
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

        // Display message if no records found for the search
        if (empty($search_results)) {
            $no_results_message = "No results found for: '" . htmlspecialchars($_GET['search']) . "'";
        }
    }
}

// Fetch all patients only if no search is performed
if (!$is_search && empty($search_results)) {
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

                <li class="active"><a href="patient_management.php">
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
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                        </svg>
                        Eyes Check</a>

                <li><a href="receipt.php">
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

                <li><a href="logout.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="20" x2="18" y2="10"></line>
                            <line x1="12" y1="20" x2="12" y2="4"></line>
                            <line x1="6" y1="20" x2="6" y2="14"></line>
                        </svg>
                        Log out</a></li>

                </li>
            </ul>
        </aside>
        <main class="main-content">

            <div class="staff-name">Welcome, <?php echo htmlspecialchars($_SESSION['staff_name']); ?></div>

            <div class="header">
                <h1>Patient Management</h1>
                <button class="new-patient-button" name="new_patient" onclick="clearForm()">+ New Patient</button>
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
                        <!-- <input type="number" id="phone" name="phone" required> -->
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
                            <th>PATIENT ID</th>
                            <th>FIRST NAME</th>
                            <th>LAST NAME</th>
                            <th>GENDER</th>
                            <th>EMAIL</th>
                            <th>PHONE</th>
                            <th>DATE OF BIRTH</th>
                            <th>ADDRESS</th>
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
                                <td><?php echo htmlspecialchars($patient['address']); ?></td>
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
        <?php endif; ?>
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

        const phoneInput = document.getElementById("phone");
        const phoneError = document.getElementById("phoneError");
        const patientForm = document.getElementById("patientForm");

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
        patientForm.addEventListener("submit", function(e) {
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

            // Check age range (assuming patients should be between 0 and 120 years old)
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
        patientForm.addEventListener("submit", function(e) {
            if (dobInput.value) {
                const validation = validateDob(dobInput.value);
                if (!validation.valid) {
                    e.preventDefault();
                    showDobError(validation.message);
                    dobInput.focus();
                }
            }
        });
    </script>
</body>

</html>