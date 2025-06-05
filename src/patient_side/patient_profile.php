<?php
session_start();
include("../db_config.php");

// Check if patient is logged in
if (!isset($_SESSION['patient_id'])) {
    header("Location: patient_login.php");
    exit();
}

$edit_mode = false;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $edit_mode = true; // Stay in edit mode if there are errors
} elseif (!empty($errors)) {
    $edit_mode = true; // Also stay in edit mode if there are other errors
}
$patient_id = $_SESSION['patient_id'];
$errors = [];
$success = '';
$password_errors = [];
$password_success = '';

// Fetch patient data
$sql = "SELECT * FROM patient WHERE patient_id = '$patient_id'";
$result = mysqli_query($conn, $sql);
$patient = mysqli_fetch_assoc($result);

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $dob = mysqli_real_escape_string($conn, $_POST['dob']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    // Validation
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($gender)) $errors[] = "Gender is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (!preg_match('/^20\d{8}$/', $phone)) $errors[] = "Phone must start with 20 and be 10 digits";

    // Date validation
    if (empty($dob)) {
        $errors[] = "Date of birth is required";
    } else {
        $dobTime = strtotime($dob);
        $currentTime = time();
        $minTime = strtotime('-120 years', $currentTime);

        if ($dobTime > $currentTime) {
            $errors[] = "Date of birth cannot be in the future";
        } elseif ($dobTime < $minTime) {
            $errors[] = "Age cannot be more than 120 years";
        }
    }

    // Check if email exists (excluding current user)
    $email_check = "SELECT * FROM patient WHERE email = '$email' AND patient_id != '$patient_id'";
    $result = mysqli_query($conn, $email_check);
    if (mysqli_num_rows($result) > 0) {
        $errors[] = "Email already registered";
    }

    // Check if phone exists (excluding current user)
    $phone_check = "SELECT * FROM patient WHERE phone = '$phone' AND patient_id != '$patient_id'";
    $phone_result = mysqli_query($conn, $phone_check);
    if (mysqli_num_rows($phone_result) > 0) {
        $errors[] = "Phone number already registered";
    }

    if (empty($errors)) {
        $sql = "UPDATE patient SET 
                first_name = '$first_name', 
                last_name = '$last_name', 
                gender = '$gender', 
                dob = '$dob', 
                email = '$email', 
                phone = '$phone', 
                address = '$address' 
                WHERE patient_id = '$patient_id'";

        if (mysqli_query($conn, $sql)) {
            $success = "Profile updated successfully!";
            $edit_mode = false; // Add this line to exit edit mode after successful update
            // Refresh patient data
            $result = mysqli_query($conn, "SELECT * FROM patient WHERE patient_id = '$patient_id'");
            $patient = mysqli_fetch_assoc($result);
        } else {
            $errors[] = "Error updating profile: " . mysqli_error($conn);
        }
    }
}

// Handle password change
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Verify current password
    if (!password_verify($current_password, $patient['password'])) {
        $password_errors[] = "Current password is incorrect";
    }
    if (strlen($new_password) < 8) {
        $password_errors[] = "New password must be at least 8 characters";
    }
    if ($new_password !== $confirm_password) {
        $password_errors[] = "New passwords do not match";
    }

    if (empty($password_errors)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE patient SET password = '$hashed_password' WHERE patient_id = '$patient_id'";

        if (mysqli_query($conn, $sql)) {
            $password_success = "Password changed successfully!";
        } else {
            $password_errors[] = "Error changing password: " . mysqli_error($conn);
        }
    }
}

// Handle account deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_account'])) {
    $confirm_delete = $_POST['confirm_delete'];

    if ($confirm_delete === 'DELETE') {
        // Delete all appointments first to maintain referential integrity
        $delete_appointments = "DELETE FROM appointment WHERE patient_id = '$patient_id'";
        mysqli_query($conn, $delete_appointments);

        // Then delete the patient
        $delete_patient = "DELETE FROM patient WHERE patient_id = '$patient_id'";
        if (mysqli_query($conn, $delete_patient)) {
            session_destroy();
            header("Location: patient_login.php");
            exit();
        } else {
            $errors[] = "Error deleting account: " . mysqli_error($conn);
        }
    } else {
        $errors[] = "Please type DELETE to confirm account deletion";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Profile - Vision Care</title>
    <link rel="stylesheet" href="patient_login.css">
    <style>
        /* Base styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
        }

        .container {
            width: 100%;
            padding: 0 15px;
            box-sizing: border-box;
        }

        header {
            background-color: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .icon {
            width: 24px;
            height: 24px;
            margin-right: 10px;
        }

        .auth-links {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .auth-links a {
            color: #3498db;
            text-decoration: none;
        }

        /* Profile container */
        .profile-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 0 15px;
            box-sizing: border-box;
        }

        /* Profile sections */
        .profile-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .profile-section h2 {
            font-size: 1.3rem;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        /* Form styles */
        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="date"],
        .form-group input[type="password"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            box-sizing: border-box;
        }

        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }

        /* Gender options */
        .gender-options {
            display: flex;
            gap: 15px;
        }

        .gender-option {
            display: flex;
            align-items: center;
        }

        .gender-option input {
            margin-right: 5px;
        }

        /* Button styles */
        .btn {
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.2s;
            text-decoration: none;
            border: none;
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }

        /* Password strength */
        .password-strength {
            height: 5px;
            margin-top: 5px;
            background: #eee;
            border-radius: 3px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s;
            background: #e74c3c;
        }

        /* Messages */
        .alert {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Responsive adjustments */
        @media (max-width: 600px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .gender-options {
                /* flex-direction: column; */
                gap: 5px;
            }

            .profile-section {
                padding: 15px;
            }
        }

        /* Form rows for side-by-side fields */
        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-row .form-group {
            flex: 1;
        }

        /* Password toggle */
        .password-input {
            position: relative;
        }

        .password-input input {
            padding-right: 40px;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
        }

        .toggle-password svg {
            width: 20px;
            height: 20px;
            fill: #7f8c8d;
        }

        /* New styles for edit mode */
        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .profile-actions {
            display: flex;
            gap: 10px;
        }

        .edit-btn,
        .save-btn {
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
        }

        .edit-btn {
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            color: #333;
        }

        .save-btn {
            background-color: #3498db;
            border: 1px solid #3498db;
            color: white;
        }

        .save-btn:hover {
            background-color: #2980b9;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
            margin-left: 10px;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        input[disabled],
        textarea[disabled],
        select[disabled] {
            background-color: #f9f9f9;
            color: #666;
            cursor: not-allowed;
        }

        input[disabled]::placeholder {
            color: #ccc;
        }

        /* Modal styles for delete confirmation */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            display: none;
        }

        .modal-content {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .modal-title {
            font-size: 1.2rem;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .modal-message {
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .modal-btn {
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s;
            border: none;
        }

        .modal-btn-cancel {
            background-color: #f1f1f1;
            color: #333;
        }

        .modal-btn-cancel:hover {
            background-color: #e1e1e1;
        }

        .modal-btn-danger {
            background-color: #e74c3c;
            color: white;
        }

        .modal-btn-danger:hover {
            background-color: #c0392b;
        }
    </style>
</head>

<body>
    <header>
        <div class="container header-content">
            <div class="logo">
                <svg viewBox="0 0 24 24" fill="currentColor" class="icon">
                    <path d="M12 4a4 4 0 100 8 4 4 0 000-8zM2 12C2 6.486 6.486 2 12 2s10 4.486 10 10-4.486 10-10 10S2 17.514 2 12z"></path>
                </svg>
                <span>Vision Care</span>
            </div>
            <div class="auth-links">
                <a href="patient_history.php">Appointments</a>
                <a href="patient_logout.php">Logout</a>
            </div>
        </div>
    </header>

    <main class="container profile-container">
        <h1>Patient Profile</h1>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Personal Information Section -->
        <div class="profile-section">
            <div class="profile-header">
                <h2>Personal Information</h2>
                <!-- And add this instead -->
                <button type="button" class="edit-btn" id="editBtn">Edit Profile</button>
            </div>

            <form method="POST" action="patient_profile.php" id="profileForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" required disabled
                            value="<?php echo htmlspecialchars($patient['first_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" required disabled
                            value="<?php echo htmlspecialchars($patient['last_name'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Gender</label>
                    <div class="gender-options">
                        <label class="gender-option">
                            <input type="radio" name="gender" value="Male" required
                                <?php echo ($patient['gender'] ?? '') == 'Male' ? 'checked' : ''; ?>
                                <?php echo !$edit_mode ? 'disabled' : ''; ?>> Male
                        </label>
                        <label class="gender-option">
                            <input type="radio" name="gender" value="Female" required
                                <?php echo ($patient['gender'] ?? '') == 'Female' ? 'checked' : ''; ?>
                                <?php echo !$edit_mode ? 'disabled' : ''; ?>> Female
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="dob">Date of Birth</label>
                    <input type="date" id="dob" name="dob" required disabled
                        min="<?php echo date('Y-m-d', strtotime('-120 years')); ?>"
                        max="<?php echo date('Y-m-d'); ?>"
                        value="<?php echo htmlspecialchars($patient['dob'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required disabled
                        value="<?php echo htmlspecialchars($patient['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" required disabled
                        placeholder="20xxxxxxxx" maxlength="10"
                        value="<?php echo htmlspecialchars($patient['phone'] ?? ''); ?>">
                    <small>Must start with 20 and be 10 digits (e.g., 2012345678)</small>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" disabled><?php echo htmlspecialchars($patient['address'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <button type="submit" name="update_profile" class="btn btn-primary" id="update-btn" style="display:none;">Update Profile</button>
                    <button type="button" class="btn btn-secondary" id="cancel-edit" style="display:none;" onclick="disableEditMode()">Cancel</button>
                </div>
            </form>
        </div>

        <!-- Change Password Section -->
        <div class="profile-section">
            <h2>Change Password</h2>

            <?php if (!empty($password_errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($password_errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($password_success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($password_success); ?></div>
            <?php endif; ?>

            <form method="POST" action="patient_profile.php">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <div class="password-input">
                        <input type="password" id="current_password" name="current_password" required>
                        <button type="button" class="toggle-password" onclick="togglePasswordVisibility('current_password')">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <div class="password-input">
                        <input type="password" id="new_password" name="new_password" required
                            oninput="checkPasswordStrength(this.value)">
                        <button type="button" class="toggle-password" onclick="togglePasswordVisibility('new_password')">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="password-strength-bar"></div>
                    </div>
                    <small>Use 8+ characters with numbers and symbols</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="password-input">
                        <input type="password" id="confirm_password" name="confirm_password" required
                            oninput="checkPasswordMatch()">
                        <button type="button" class="toggle-password" onclick="togglePasswordVisibility('confirm_password')">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"></path>
                            </svg>
                        </button>
                    </div>
                    <p class="error-message" id="password-match-error" style="display:none;color:#e74c3c;">Passwords do not match</p>
                </div>

                <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
            </form>
        </div>

        <!-- Delete Account Section - Simplified -->
        <div class="profile-section">
            <h2>Delete Account</h2>
            <p>Warning: This action is permanent and cannot be undone. All your appointments and data will be deleted.</p>
            <button type="button" id="delete-account-btn" class="btn btn-danger">Delete My Account</button>
        </div>
    </main>

    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="delete-modal">
        <div class="modal-content">
            <div class="modal-title">Delete Personal Account</div>
            <div class="modal-message">Are you sure to delete this account? This action is not reversible, so please continue with caution.</div>
            <div class="modal-actions">
                <button type="button" class="modal-btn modal-btn-cancel" id="cancel-delete">Cancel</button>
                <form method="POST" action="patient_profile.php" style="display: inline;">
                    <input type="hidden" name="confirm_delete" value="DELETE">
                    <button type="submit" name="delete_account" class="modal-btn modal-btn-danger">Delete Account</button>
                </form>
            </div>
        </div>
    </div>
    </main>

    <script>
        // Toggle password visibility
        function togglePasswordVisibility(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling.querySelector('svg');

            if (field.type === 'password') {
                field.type = 'text';
                icon.innerHTML = `<path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>`;
            } else {
                field.type = 'password';
                icon.innerHTML = `<path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"></path>`;
            }
        }

        // Password strength indicator
        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('password-strength-bar');

            // Reset
            strengthBar.style.width = '0%';
            strengthBar.style.backgroundColor = '#e74c3c';

            if (password.length === 0) return;

            // Strength calculation
            let strength = 0;
            if (password.length >= 8) strength += 1;
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;

            // Update UI
            const width = (strength / 4) * 100;
            strengthBar.style.width = `${width}%`;

            // Color coding
            if (width >= 75) {
                strengthBar.style.backgroundColor = '#2ecc71';
            } else if (width >= 50) {
                strengthBar.style.backgroundColor = '#f39c12';
            }
        }

        // Password match checker
        function checkPasswordMatch() {
            const password = document.getElementById('new_password').value;
            const confirm = document.getElementById('confirm_password').value;
            const error = document.getElementById('password-match-error');

            if (confirm.length > 0 && password !== confirm) {
                error.style.display = 'block';
            } else {
                error.style.display = 'none';
            }
        }

        // Phone number validation
        document.getElementById('phone').addEventListener('input', function(e) {
            // Remove all non-digit characters
            this.value = this.value.replace(/\D/g, '');

            // Ensure it starts with 20
            if (this.value.length === 1 && this.value !== '2') {
                this.value = '2';
            } else if (this.value.length === 2 && !this.value.startsWith('20')) {
                this.value = '20';
            }

            // Limit to 10 digits
            if (this.value.length > 10) {
                this.value = this.value.slice(0, 10);
            }
        });

        // Edit toggle functionality
        document.getElementById('editBtn').addEventListener('click', function() {
            enableEditMode();
        });

        function enableEditMode() {
            const inputs = document.querySelectorAll('#profileForm input, #profileForm textarea, #profileForm select');
            const radioInputs = document.querySelectorAll('#profileForm input[type="radio"]');

            // Enable all inputs
            inputs.forEach(input => {
                input.disabled = false;
            });

            // Enable radio buttons
            radioInputs.forEach(radio => {
                radio.disabled = false;
            });

            // Toggle button visibility
            document.getElementById('editBtn').style.display = 'none';
            document.getElementById('update-btn').style.display = 'block';
        }

        function disableEditMode() {
            const inputs = document.querySelectorAll('#profileForm input, #profileForm textarea, #profileForm select');
            const radioInputs = document.querySelectorAll('#profileForm input[type="radio"]');

            // Disable all inputs
            inputs.forEach(input => {
                input.disabled = true;
            });

            // Disable radio buttons
            radioInputs.forEach(radio => {
                radio.disabled = true;
            });

            // Toggle button visibility
            document.getElementById('editBtn').style.display = 'block';
            document.getElementById('update-btn').style.display = 'none';
        }

        // Reset form when there are errors
        <?php if ($edit_mode): ?>
            document.addEventListener('DOMContentLoaded', function() {
                enableEditMode();
            });
        <?php else: ?>
            document.addEventListener('DOMContentLoaded', function() {
                disableEditMode();
            });
        <?php endif; ?>

        // Delete account modal
        const deleteBtn = document.getElementById('delete-account-btn');
        const deleteModal = document.getElementById('delete-modal');
        const cancelDelete = document.getElementById('cancel-delete');

        deleteBtn.addEventListener('click', function() {
            deleteModal.style.display = 'flex';
        });

        cancelDelete.addEventListener('click', function() {
            deleteModal.style.display = 'none';
        });

        // Close modal when clicking outside
        deleteModal.addEventListener('click', function(e) {
            if (e.target === deleteModal) {
                deleteModal.style.display = 'none';
            }
        });
    </script>
</body>

</html>