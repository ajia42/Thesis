<?php
session_start();
include("../db_config.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

// If user is already logged in, redirect to dashboard or home page
if (isset($_SESSION['user_name'])) {
    header("Location: user_history.php"); // Replace with your dashboard page
    exit();
}

if (isset($_SESSION['registered_phone']) && !isset($_SESSION['user_name'])) {
    // User started registration but didn't complete it
    header("Location: user_info.php");
    exit();
}



$errors = [];
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_name = mysqli_real_escape_string($conn, $_POST['user_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($user_name)) $errors[] = "Username is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (!preg_match('/^20\d{8}$/', $phone)) {
        $errors[] = "Phone must start with 20 and be 10 digits (e.g., 2012345678)";
    }
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match";

    // Check if email exists (for both new and existing users)
    $email_check = "SELECT * FROM user WHERE email = '$email' AND phone != '$phone'";
    $email_result = mysqli_query($conn, $email_check);
    if (mysqli_num_rows($email_result) > 0) {
        $errors[] = "Email already registered with another account";
    }

    if (empty($errors)) {
        // Check if phone exists in user table
        $phone_check = "SELECT * FROM user WHERE phone = '$phone'";
        $phone_result = mysqli_query($conn, $phone_check);

        if (mysqli_num_rows($phone_result) > 0) {
            // Phone exists - check if it's a complete registration or walk-in record
            $user = mysqli_fetch_assoc($phone_result);

            if ($user['user_name'] !== NULL && $user['email'] !== NULL && $user['password'] !== NULL) {
                $errors[] = "This phone number is already registered. Please login instead.";
            } else {
                // Walk-in patient - update their record with registration details
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $sql = "UPDATE user SET 
                        user_name = '$user_name', 
                        email = '$email', 
                        password = '$hashed_password' 
                        WHERE phone = '$phone'";

                if (mysqli_query($conn, $sql)) {
                    // Check if patient exists in patient table
                    $patient_check = "SELECT * FROM patient WHERE phone = '$phone'";
                    $patient_result = mysqli_query($conn, $patient_check);

                    if (mysqli_num_rows($patient_result) > 0) {
                        // Walk-in patient exists - redirect to login
                        $success = "Registration completed successfully! You can now login.";
                        header("Refresh: 3; url=user_login.php");
                    } else {
                        // New patient - redirect to info page
                        $_SESSION['registered_phone'] = $phone;
                        header("Location: user_info.php");
                        exit();
                    }
                } else {
                    $errors[] = "Error updating your registration. Please try again.";
                }
            }
        } else {
            // Phone doesn't exist in user table - new registration
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO user (user_name, email, phone, password) 
                    VALUES ('$user_name', '$email', '$phone', '$hashed_password')";

            if (mysqli_query($conn, $sql)) {
                $_SESSION['registered_phone'] = $phone;
                header("Location: user_info.php");
                exit();
            } else {
                $errors[] = "Error creating your account. Please try again.";
            }
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
    <title>Patient Registration - Vision Care</title>
    <link rel="stylesheet" href="user_login.css">
    <style>
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

        .password-hint {
            font-size: 0.8rem;
            color: #7f8c8d;
            margin-top: 0.3rem;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 0.95rem;
            text-align: center;
        }

        .error-message {
            color: #e74c3c;
            font-size: 0.8rem;
            margin-top: 5px;
        }

        .password-input {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
        }

        .eye-icon {
            width: 20px;
            height: 20px;
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
                <a href="user_login.php">Login</a>
                <a href="user_register.php" class="active">Register</a>
            </div>
        </div>
    </header>

    <main class="container sign-in-container">
        <div class="sign-in-card">
            <div class="logo-center">
                <svg viewBox="0 0 24 24" fill="currentColor" class="icon-large">
                    <path d="M12 4a4 4 0 100 8 4 4 0 000-8zM2 12C2 6.486 6.486 2 12 2s10 4.486 10 10-4.486 10-10 10S2 17.514 2 12z"></path>
                </svg>
                <h2>Vision Care</h2>
            </div>

            <h1>Patient Registration</h1>
            <p class="create-account">Already have an account? <a href="user_login.php">Sign in here</a></p>

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

            <form method="POST" action="user_register.php">
                <div class="input-group">
                    <label for="user_name">Username</label>
                    <input type="text" id="user_name" name="user_name" required
                        value="<?php echo htmlspecialchars($_POST['user_name'] ?? ''); ?>">
                </div>

                <div class="input-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <div class="input-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" required
                        placeholder="10 digits starting with 20" maxlength="10"
                        value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    <small class="hint">Must start with 20 and be 10 digits (e.g., 2012345678)</small>
                </div>

                <div class="input-group">
                    <label for="password">Password</label>
                    <div class="password-input">
                        <input type="password" id="password" name="password" required
                            oninput="checkPasswordStrength(this.value)">
                        <button type="button" class="toggle-password" onclick="togglePasswordVisibility('password')">
                            <svg viewBox="0 0 24 24" fill="currentColor" class="eye-icon">
                                <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="password-strength-bar"></div>
                    </div>
                    <p class="password-hint" id="password-hint">Use 8+ characters with numbers and symbols</p>
                </div>

                <div class="input-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="password-input">
                        <input type="password" id="confirm_password" name="confirm_password" required
                            oninput="checkPasswordMatch()">
                        <button type="button" class="toggle-password" onclick="togglePasswordVisibility('confirm_password')">
                            <svg viewBox="0 0 24 24" fill="currentColor" class="eye-icon">
                                <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"></path>
                            </svg>
                        </button>
                    </div>
                    <p class="error-message" id="password-match-error" style="display:none;">Passwords do not match</p>
                </div>

                <button type="submit" class="sign-in-button">
                    <svg viewBox="0 0 24 24" fill="currentColor" class="arrow-icon">
                        <path d="M10 17l5-5-5-5v10z"></path>
                        <path d="M19 12c0 4.14-3.36 7.5-7.5 7.5S4 16.14 4 12 7.36 4.5 12 4.5s7.5 3.36 7.5 7.5zM12 6.5c-3.04 0-5.5 2.46-5.5 5.5s2.46 5.5 5.5 5.5 5.5-2.46 5.5-5.5-2.46-5.5-5.5-5.5z"></path>
                    </svg>
                    Register
                </button>
            </form>
        </div>
    </main>

    <script>
        function togglePasswordVisibility(fieldId) {
            const field = document.getElementById(fieldId);
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
            const password = document.getElementById('password').value;
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

        // Prevent paste of non-numeric characters
        document.getElementById('phone').addEventListener('paste', function(e) {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text');
            const numbers = text.replace(/\D/g, '');
            document.execCommand('insertText', false, numbers);
        });
    </script>
</body>

</html>