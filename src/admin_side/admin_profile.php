<?php
session_start();
include("../db_config.php");

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: signin_admin.php");
    exit();
}

$errors = [];
$success = '';

// Fetch admin data
$admin_id = $_SESSION['admin_id'];
$query = "SELECT * FROM admin WHERE admin_id = '$admin_id'";
$result = mysqli_query($conn, $query);
$admin = mysqli_fetch_assoc($result);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle form submission
    $user_name = mysqli_real_escape_string($conn, $_POST['user_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Basic validations
    if (empty($user_name)) $errors[] = "Username is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";

    // Check if email is changed and already exists
    if ($email != $admin['email']) {
        $email_check = "SELECT * FROM admin WHERE email = '$email'";
        $result = mysqli_query($conn, $email_check);
        if (mysqli_num_rows($result) > 0) {
            $errors[] = "Email already registered";
        }
    }

    // Check if username is changed and already exists
    if ($user_name != $admin['user_name']) {
        $username_check = "SELECT * FROM admin WHERE user_name = '$user_name'";
        $username_result = mysqli_query($conn, $username_check);
        if (mysqli_num_rows($username_result) > 0) {
            $errors[] = "Username already taken";
        }
    }

    // Password change validation
    $password_changed = false;
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        if (!password_verify($current_password, $admin['password'])) {
            $errors[] = "Current password is incorrect";
        }
        if (strlen($new_password) < 8) $errors[] = "New password must be at least 8 characters";
        if ($new_password !== $confirm_password) $errors[] = "New passwords do not match";
        $password_changed = true;
    }

    if (empty($errors)) {
        // Prepare update query
        $update_fields = [
            "user_name = '$user_name'",
            "email = '$email'"
        ];

        if ($password_changed) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_fields[] = "password = '$hashed_password'";
        }

        $sql = "UPDATE admin SET " . implode(", ", $update_fields) . " WHERE admin_id = '$admin_id'";

        if (mysqli_query($conn, $sql)) {
            $success = "Profile updated successfully!";
            // Refresh admin data
            $result = mysqli_query($conn, $query);
            $admin = mysqli_fetch_assoc($result);
        } else {
            $errors[] = "Error updating profile: " . mysqli_error($conn);
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
    <title>Admin Profile - Vision Care</title>
    <link rel="stylesheet" href="signin_admin.css">
    <style>
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
            background-color: #1e88e5;
            border: 1px solid #1e88e5;
            color: white;
        }

        .save-btn:hover {
            background-color: #1565c0;
        }

        .password-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .password-section h3 {
            margin-bottom: 15px;
            color: #333;
        }

        input[disabled] {
            background-color: #f9f9f9;
            color: #666;
        }

        .auth-links {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .back-link {
            text-decoration: none;
            color: #555;
            font-weight: 500;
            padding: 8px 12px;
            border-radius: 4px;
            background-color: #f0f0f0;
            transition: background-color 0.3s;
        }

        .back-link:hover {
            background-color: #e0e0e0;
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
                <a href="patient_management.php" class="back-link">Back</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </header>

    <main class="container sign-in-container">
        <div class="sign-in-card">
            <div class="profile-header">
                <div class="logo-center">
                    <svg viewBox="0 0 24 24" fill="currentColor" class="icon-large">
                        <path d="M12 4a4 4 0 100 8 4 4 0 000-8zM2 12C2 6.486 6.486 2 12 2s10 4.486 10 10-4.486 10-10 10S2 17.514 2 12z"></path>
                    </svg>
                    <h2>Admin Profile</h2>
                </div>
                <div class="profile-actions">
                    <button type="button" class="edit-btn" id="editBtn">Edit Profile</button>
                    <button type="submit" class="save-btn" id="saveBtn" form="profileForm" style="display:none;">Save Changes</button>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" action="admin_profile.php" id="profileForm">
                <div class="input-group">
                    <label for="user_name">Username</label>
                    <input type="text" id="user_name" name="user_name" required
                        value="<?php echo htmlspecialchars($admin['user_name'] ?? ''); ?>" disabled>
                </div>

                <div class="input-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required
                        value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>" disabled>
                </div>

                <div class="password-section">
                    <h3>Change Password</h3>

                    <div class="input-group">
                        <label for="current_password">Current Password</label>
                        <div class="password-input">
                            <input type="password" id="current_password" name="current_password" disabled>
                            <button type="button" class="toggle-password" onclick="togglePasswordVisibility('current_password')">
                                <svg viewBox="0 0 24 24" fill="currentColor" class="eye-icon">
                                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="new_password">New Password</label>
                        <div class="password-input">
                            <input type="password" id="new_password" name="new_password" disabled
                                oninput="checkPasswordStrength(this.value)">
                            <button type="button" class="toggle-password" onclick="togglePasswordVisibility('new_password')">
                                <svg viewBox="0 0 24 24" fill="currentColor" class="eye-icon">
                                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="password-strength-bar"></div>
                        </div>
                        <p class="password-hint" id="password-hint">Leave blank to keep current password</p>
                    </div>

                    <div class="input-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <div class="password-input">
                            <input type="password" id="confirm_password" name="confirm_password" disabled
                                oninput="checkPasswordMatch()">
                            <button type="button" class="toggle-password" onclick="togglePasswordVisibility('confirm_password')">
                                <svg viewBox="0 0 24 24" fill="currentColor" class="eye-icon">
                                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"></path>
                                </svg>
                            </button>
                        </div>
                        <p class="error-message" id="password-match-error" style="display:none;color:#e74c3c;">Passwords do not match</p>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Toggle edit mode
        document.getElementById('editBtn').addEventListener('click', function() {
            const inputs = document.querySelectorAll('#profileForm input:not([type="hidden"])');
            inputs.forEach(input => {
                input.disabled = !input.disabled;
            });

            document.getElementById('editBtn').style.display = 'none';
            document.getElementById('saveBtn').style.display = 'block';
        });

        function togglePasswordVisibility(fieldId) {
            const field = document.getElementById(fieldId);
            if (field.disabled) return;

            const icon = field.nextElementSibling.querySelector('.eye-icon');
            if (field.type === 'password') {
                field.type = 'text';
                icon.innerHTML = `<path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>`;
            } else {
                field.type = 'password';
                icon.innerHTML = `<path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>`;
            }
        }

        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('password-strength-bar');
            const hint = document.getElementById('password-hint');

            // Reset
            strengthBar.style.width = '0%';
            strengthBar.style.backgroundColor = '#e74c3c';
            hint.style.color = '#7f8c8d';

            if (password.length === 0) {
                hint.textContent = 'Leave blank to keep current password';
                return;
            }

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
                hint.textContent = 'Strong password!';
                hint.style.color = '#2ecc71';
            } else if (width >= 50) {
                strengthBar.style.backgroundColor = '#f39c12';
                hint.textContent = 'Good, but could be stronger';
            } else {
                hint.textContent = 'Weak - add numbers/symbols';
            }
        }

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
    </script>
</body>

</html>