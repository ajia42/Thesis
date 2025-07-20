<?php
session_start();
include("../db_config.php");

if (isset($_SESSION['admin_id'])) {
    header('Location: patient_management.php');
    exit();
}

// Check if an admin already exists in the database
$admin_check = "SELECT * FROM admin WHERE role = 'admin' LIMIT 1";
$admin_result = mysqli_query($conn, $admin_check);
if (mysqli_num_rows($admin_result) > 0) {
    header('Location: signin_admin.php');
    exit();
}

$errors = [];
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_name = mysqli_real_escape_string($conn, $_POST['user_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Input validation
    if (empty($user_name)) $errors[] = "Username is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }
    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9\W]/', $password)) {
        $errors[] = "Password must contain both letters and numbers/symbols";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    // Check if email already exists
    $email_check = "SELECT * FROM admin WHERE email = '$email'";
    $result = mysqli_query($conn, $email_check);
    if (mysqli_num_rows($result) > 0) {
        $errors[] = "ອີເມວຖືກໃຊ້ໄປແລ້ວ";
    }

    if (empty($errors)) {
        // Get the highest existing admin_id and increment it
        $id_query = "SELECT MAX(CAST(SUBSTRING(admin_id, 2) AS UNSIGNED)) as max_id FROM admin";
        $id_result = mysqli_query($conn, $id_query);
        $row = mysqli_fetch_assoc($id_result);
        $next_id = ($row['max_id']) ? $row['max_id'] + 1 : 1;
        $admin_id = 'A' . str_pad($next_id, 4, '0', STR_PAD_LEFT);

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO admin (admin_id, user_name, email, password, role) 
                VALUES ('$admin_id', '$user_name', '$email', '$hashed_password', 'admin')";

        if (mysqli_query($conn, $sql)) {
            $success = "ລົງທະບຽນສຳເລັດ, ກໍາລັງກັບໄປໜ້າເຂົ້າສູ່ລະບົບ...";
            header("Refresh: 3; url=signin_admin.php");
        } else {
            $errors[] = "Error: " . mysqli_error($conn);
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
    <title>Register Admin - Vision Care</title>
    <link rel="stylesheet" href="signin_admin.css">
    <link rel="icon" href="../images/logo.svg" type="image/svg+xml">
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

        .password-error {
            color: #e74c3c;
            font-size: 0.8rem;
            margin-top: 5px;
            display: none;
        }
    </style>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Phetsarath:wght@400;700&display=swap" rel="stylesheet">
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
                <a href="signin_admin.php">ເຂົ້າສູ່ລະບົບ</a>
                <a href="register_admin.php" class="active">ລົງທະບຽນ</a>
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

            <h1>ລົງທະບຽນໃໝ່</h1>
            <p class="create-account">Or <a href="signin_admin.php">ມີບັນຊີແລ້ວ? ເຂົ້າສູ່ລະບົບ</a></p>

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

            <form method="POST" action="register_admin.php" onsubmit="return validatePassword()">
                <div class="input-group">
                    <label for="user_name">ຊື່ຜູ້ໃຊ້</label>
                    <input type="text" id="user_name" name="user_name" required
                        value="<?php echo htmlspecialchars($_POST['user_name'] ?? ''); ?>">
                </div>

                <div class="input-group">
                    <label for="email">ອີເມວ</label>
                    <input type="email" id="email" name="email" required
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <div class="input-group">
                    <label for="password">ລະຫັດຜ່ານ</label>
                    <div class="password-input">
                        <input type="password" id="password" name="password" required
                            onkeydown="validatePasswordInput(event)"
                            oninput="checkPasswordStrength(this.value)">
                        <button type="button" class="toggle-password" onclick="togglePasswordVisibility('password')">
                            <svg viewBox="0 0 24 24" fill="currentColor" class="eye-icon">
                                <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"></path>
                            </svg>
                        </button>
                    </div>
                    <!-- <div class="password-strength">
                        <div class="password-strength-bar" id="password-strength-bar"></div>
                    </div> -->
                    <p class="password-hint" id="password-hint">ລະຫັດຕ້ອງມີ 8 ໂຕຂຶ້ນໄປ, ຢ່າງໜ້ອຍຕ້ອງມີ 1 ຕົວໜັງສື 1 ຕົວເລກ</p>
                    <!-- <p class="password-error" id="password-error">ລະຫັດຜ່ານສາມາດປ້ອນໄດ້ພຽງແຕ່ຕົວອັກສອນອັງກິດ, ຕົວເລກ, ແລະສັນຍາລັກ !@#$%^&*()_+-=[]{};':"\|,.<>/?</p> -->
                </div>

                <div class="input-group">
                    <label for="confirm_password">ຢືນຢັນລະຫັດຜ່ານ</label>
                    <div class="password-input">
                        <input type="password" id="confirm_password" name="confirm_password" required
                            onkeydown="validatePasswordInput(event)"
                            oninput="checkPasswordMatch()">
                        <button type="button" class="toggle-password" onclick="togglePasswordVisibility('confirm_password')">
                            <svg viewBox="0 0 24 24" fill="currentColor" class="eye-icon">
                                <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"></path>
                            </svg>
                        </button>
                    </div>
                    <p class="error-message" id="password-match-error" style="display:none;color:#e74c3c;">Passwords do not match</p>
                </div>

                <button type="submit" class="sign-in-button">
                    <svg viewBox="0 0 24 24" fill="currentColor" class="arrow-icon">
                        <path d="M10 17l5-5-5-5v10z"></path>
                        <path d="M19 12c0 4.14-3.36 7.5-7.5 7.5S4 16.14 4 12 7.36 4.5 12 4.5s7.5 3.36 7.5 7.5zM12 6.5c-3.04 0-5.5 2.46-5.5 5.5s2.46 5.5 5.5 5.5 5.5-2.46 5.5-5.5-2.46-5.5-5.5-5.5z"></path>
                    </svg>
                    ລົງທະບຽນ
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

        function validatePasswordInput(event) {
            // Only allow English letters, numbers, and common symbols
            const allowedChars = /^[A-Za-z0-9!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]*$/;

            // Check if the pressed key is allowed
            if (!allowedChars.test(event.key)) {
                event.preventDefault();
                document.getElementById('password-error').style.display = 'block';
                return false;
            } else {
                document.getElementById('password-error').style.display = 'none';
            }
        }

        function validatePassword() {
            const password = document.getElementById('password').value;
            const allowedChars = /^[A-Za-z0-9!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]*$/;

            if (!allowedChars.test(password)) {
                document.getElementById('password-error').style.display = 'block';
                return false;
            }
            return true;
        }

        // function checkPasswordStrength(password) {
        //     const strengthBar = document.getElementById('password-strength-bar');
        //     const hint = document.getElementById('password-hint');

        //     // Reset
        //     if (strengthBar) strengthBar.style.width = '0%';
        //     if (strengthBar) strengthBar.style.backgroundColor = '#e74c3c';
        //     hint.style.color = '#7f8c8d';

        //     if (password.length === 0) {
        //         hint.textContent = 'ລະຫັດຕ້ອງມີ 8 ໂຕຂຶ້ນໄປ, ຢ່າງໜ້ອຍຕ້ອງມີ 1 ຕົວໜັງສື 1 ຕົວເລກ';
        //         return;
        //     }

        //     // Check requirements
        //     const hasMinLength = password.length >= 8;
        //     const hasLetter = /[A-Za-z]/.test(password);
        //     const hasNumberOrSymbol = /[0-9\W]/.test(password);

        //     // Calculate strength (0-3)
        //     let strength = 0;
        //     if (hasMinLength) strength += 1;
        //     if (hasLetter) strength += 1;
        //     if (hasNumberOrSymbol) strength += 1;

        //     // Update UI
        //     const width = (strength / 3) * 100;
        //     if (strengthBar) strengthBar.style.width = `${width}%`;

        //     // Color coding and messages
        //     if (hasMinLength && hasLetter && hasNumberOrSymbol) {
        //         if (strengthBar) strengthBar.style.backgroundColor = '#2ecc71';
        //         hint.textContent = 'Strong password!';
        //         hint.style.color = '#2ecc71';
        //     } else if (hasMinLength && (hasLetter || hasNumberOrSymbol)) {
        //         if (strengthBar) strengthBar.style.backgroundColor = '#f39c12';
        //         hint.textContent = 'Password needs both letters and numbers/symbols';
        //     } else {
        //         hint.textContent = 'Weak - must have letters and numbers/symbols';
        //     }
        // }

        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('password-strength-bar');
            const hint = document.getElementById('password-hint');

            // Reset
            strengthBar.style.width = '0%';
            strengthBar.style.backgroundColor = '#e74c3c';
            hint.style.color = '#7f8c8d';

            if (password.length === 0) {
                hint.textContent = 'ລະຫັດຕ້ອງມີ 8 ໂຕຂຶ້ນໄປ (ລວມມີຕົວອັກສອນ ແລະ ຕົວເລກຫຼືສັນຍະລັກ)';
                return;
            }

            // Check requirements
            const hasMinLength = password.length >= 8;
            const hasLetter = /[A-Za-z]/.test(password);
            const hasNumberOrSymbol = /[0-9\W]/.test(password);

            // Calculate strength (0-3)
            let strength = 0;
            if (hasMinLength) strength += 1;
            if (hasLetter) strength += 1;
            if (hasNumberOrSymbol) strength += 1;

            // Update UI
            const width = (strength / 3) * 100;
            strengthBar.style.width = `${width}%`;

            // Color coding and messages
            if (hasMinLength && hasLetter && hasNumberOrSymbol) {
                strengthBar.style.backgroundColor = '#2ecc71';
                hint.textContent = 'Strong password!';
                hint.style.color = '#2ecc71';
            } else if (hasMinLength && (hasLetter || hasNumberOrSymbol)) {
                strengthBar.style.backgroundColor = '#f39c12';
                hint.textContent = 'Password needs both letters and numbers/symbols';
            } else {
                hint.textContent = 'Weak - must have letters and numbers/symbols';
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
    </script>
</body>

</html>