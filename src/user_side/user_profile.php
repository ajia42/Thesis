<?php
session_start();
include("../db_config.php");

// Check if user is logged in - UPDATED SESSION VARIABLE
if (!isset($_SESSION['registered_phone'])) {
    header("Location: user_login.php");
    exit();
}

$edit_mode = false;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $edit_mode = true;
} elseif (!empty($errors)) {
    $edit_mode = true;
}

// UPDATED SESSION VARIABLE
$user_phone = $_SESSION['registered_phone'];
$errors = [];
$success = '';
$password_errors = [];
$password_success = '';

// Fetch user data with patient info
$sql = "SELECT u.*, p.first_name, p.last_name, p.gender, p.dob, p.address 
        FROM user u 
        LEFT JOIN patient p ON u.phone = p.phone 
        WHERE u.phone = '$user_phone'";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $user_name = mysqli_real_escape_string($conn, $_POST['user_name']);
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $dob = mysqli_real_escape_string($conn, $_POST['dob']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    // Validation
    if (empty($user_name)) $errors[] = "Username is required";
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($gender)) $errors[] = "Gender is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";

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
    $email_check = "SELECT * FROM user WHERE email = '$email' AND phone != '$user_phone'";
    $result = mysqli_query($conn, $email_check);
    if (mysqli_num_rows($result) > 0) {
        $errors[] = "Email already registered";
    }

    if (empty($errors)) {
        // Update user table
        $user_sql = "UPDATE user SET 
                    user_name = '$user_name', 
                    email = '$email' 
                    WHERE phone = '$user_phone'";

        // Update patient table
        $patient_sql = "UPDATE patient SET 
                       first_name = '$first_name', 
                       last_name = '$last_name', 
                       gender = '$gender', 
                       dob = '$dob', 
                       address = '$address' 
                       WHERE phone = '$user_phone'";

        if (mysqli_query($conn, $user_sql) && mysqli_query($conn, $patient_sql)) {
            $success = "Profile updated successfully!";
            $edit_mode = false;
            // Refresh user data
            $result = mysqli_query($conn, "SELECT u.*, p.first_name, p.last_name, p.gender, p.dob, p.address 
                                         FROM user u 
                                         LEFT JOIN patient p ON u.phone = p.phone 
                                         WHERE u.phone = '$user_phone'");
            $user = mysqli_fetch_assoc($result);
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
    if (!password_verify($current_password, $user['password'])) {
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
        $sql = "UPDATE user SET password = '$hashed_password' WHERE phone = '$user_phone'";

        if (mysqli_query($conn, $sql)) {
            $password_success = "Password changed successfully!";
        } else {
            $password_errors[] = "Error changing password: " . mysqli_error($conn);
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Vision Care</title>
    <link rel="stylesheet" href="user_login.css">
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
                <a href="user_history.php">Appointments</a>
                <a href="user_logout.php">Logout</a>
            </div>
        </div>
    </header>

    <main class="container profile-container">
        <h1>User Profile</h1>

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
                <button type="button" class="edit-btn" id="editBtn">Edit Profile</button>
            </div>

            <form method="POST" action="user_profile.php" id="profileForm">
                <div class="form-group">
                    <label for="user_name">Username</label>
                    <input type="text" id="user_name" name="user_name" required disabled
                        value="<?php echo htmlspecialchars($user['user_name'] ?? ''); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" required disabled
                            value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" required disabled
                            value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Gender</label>
                    <div class="gender-options">
                        <label class="gender-option">
                            <input type="radio" name="gender" value="Male" required
                                <?php echo ($user['gender'] ?? '') == 'Male' ? 'checked' : ''; ?>
                                <?php echo !$edit_mode ? 'disabled' : ''; ?>> Male
                        </label>
                        <label class="gender-option">
                            <input type="radio" name="gender" value="Female" required
                                <?php echo ($user['gender'] ?? '') == 'Female' ? 'checked' : ''; ?>
                                <?php echo !$edit_mode ? 'disabled' : ''; ?>> Female
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="dob">Date of Birth</label>
                    <input type="date" id="dob" name="dob" required disabled
                        min="<?php echo date('Y-m-d', strtotime('-120 years')); ?>"
                        max="<?php echo date('Y-m-d'); ?>"
                        value="<?php echo htmlspecialchars($user['dob'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required disabled
                        value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" required disabled
                        value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" disabled><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
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

            <form method="POST" action="user_profile.php">
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

        // Edit toggle functionality
        document.getElementById('editBtn').addEventListener('click', function() {
            enableEditMode();
        });

        function enableEditMode() {
            const inputs = document.querySelectorAll('#profileForm input, #profileForm textarea, #profileForm select');
            const radioInputs = document.querySelectorAll('#profileForm input[type="radio"]');

            // Enable all inputs except phone (which shouldn't be changed)
            inputs.forEach(input => {
                if (input.id !== 'phone') {
                    input.disabled = false;
                }
            });

            // Enable radio buttons
            radioInputs.forEach(radio => {
                radio.disabled = false;
            });

            // Toggle button visibility
            document.getElementById('editBtn').style.display = 'none';
            document.getElementById('update-btn').style.display = 'block';
            document.getElementById('cancel-edit').style.display = 'inline-block';
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
            document.getElementById('cancel-edit').style.display = 'none';
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
    </script>
</body>

</html>