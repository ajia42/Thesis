<?php
session_start();
include("../db_config.php");

// Redirect logged-in users to their dashboard
if (isset($_SESSION['user_name'])) {
    header("Location: user_history.php"); // Or user_profile.php
    exit();
}

// Check if user came from registration
if (!isset($_SESSION['registered_phone'])) {
    header("Location: user_register.php");
    exit();
}

$errors = [];
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $dob = mysqli_real_escape_string($conn, $_POST['dob']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $phone = $_SESSION['registered_phone'];

    // Validation
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($gender)) $errors[] = "Gender is required";

    // Enhanced DOB validation
    if (empty($dob)) {
        $errors[] = "Date of birth is required";
    } else {
        $dobTime = strtotime($dob);
        $currentTime = time();
        $minTime = strtotime('-120 years', $currentTime);
        $maxTime = strtotime('today', $currentTime);

        if ($dobTime > $maxTime) {
            $errors[] = "Date of birth cannot be in the future";
        } elseif ($dobTime < $minTime) {
            $errors[] = "Age cannot be more than 120 years";
        }
    }

    if (empty($errors)) {
        // Generate patient ID (P0001 format)
        $sql = "SELECT MAX(patient_id) AS max_id FROM patient";
        $result = mysqli_query($conn, $sql);
        $row = mysqli_fetch_assoc($result);

        $patient_id = 'P0001'; // Default if no patients exist
        if (!empty($row['max_id'])) {
            $num = (int) substr($row['max_id'], 1) + 1;
            $patient_id = 'P' . str_pad($num, 4, '0', STR_PAD_LEFT);
        }

        $sql = "INSERT INTO patient (patient_id, first_name, last_name, gender, dob, phone, address) 
                VALUES ('$patient_id', '$first_name', '$last_name', '$gender', '$dob', '$phone', '$address')";

        if (mysqli_query($conn, $sql)) {
            unset($_SESSION['registered_phone']); // Clear session
            $success = "Patient information saved successfully! Redirecting to login...";
            header("Refresh: 3; url=user_login.php");
        } else {
            $errors[] = "Error: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Patient Information - Vision Care</title>
    <link rel="stylesheet" href="user_login.css">
    <style>
        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-row .input-group {
            flex: 1;
        }

        .gender-options {
            display: flex;
            gap: 15px;
            margin-top: 5px;
        }

        .gender-option {
            display: flex;
            align-items: center;
        }

        .gender-option input {
            margin-right: 5px;
        }

        .disabled-input {
            background-color: #f5f5f5;
            cursor: not-allowed;
        }

        .error-message {
            color: #e74c3c;
            font-size: 0.8rem;
            margin-top: 5px;
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

            <h1>Complete Your Information</h1>
            <p>Please provide your personal details to complete registration</p>

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

            <form method="POST" action="user_info.php">
                <div class="form-row">
                    <div class="input-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" required
                            value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                    </div>
                    <div class="input-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" required
                            value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                    </div>
                </div>

                <div class="input-group">
                    <label>Gender</label>
                    <div class="gender-options">
                        <label class="gender-option">
                            <input type="radio" name="gender" value="Male" required
                                <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'checked' : ''; ?>> Male
                        </label>
                        <label class="gender-option">
                            <input type="radio" name="gender" value="Female"
                                <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'checked' : ''; ?>> Female
                        </label>
                    </div>
                </div>

                <div class="input-group">
                    <label for="dob">Date of Birth</label>
                    <input type="date" id="dob" name="dob" required
                        min="<?php echo date('Y-m-d', strtotime('-120 years')); ?>"
                        max="<?php echo date('Y-m-d'); ?>"
                        value="<?php echo htmlspecialchars($_POST['dob'] ?? ''); ?>">
                </div>

                <div class="input-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" class="disabled-input"
                        value="<?php echo htmlspecialchars($_SESSION['registered_phone'] ?? ''); ?>" readonly>
                </div>

                <div class="input-group">
                    <label for="address">Address (Optional)</label>
                    <input type="text" id="address" name="address"
                        value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
                </div>

                <button type="submit" class="sign-in-button">
                    <svg viewBox="0 0 24 24" fill="currentColor" class="arrow-icon">
                        <path d="M10 17l5-5-5-5v10z"></path>
                        <path d="M19 12c0 4.14-3.36 7.5-7.5 7.5S4 16.14 4 12 7.36 4.5 12 4.5s7.5 3.36 7.5 7.5zM12 6.5c-3.04 0-5.5 2.46-5.5 5.5s2.46 5.5 5.5 5.5 5.5-2.46 5.5-5.5-2.46-5.5-5.5-5.5z"></path>
                    </svg>
                    Complete Registration
                </button>
            </form>
        </div>
    </main>
    <script>
        // Client-side validation for date of birth
        document.getElementById('dob').addEventListener('change', function() {
            const dobInput = this;
            const selectedDate = new Date(dobInput.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            const minDate = new Date();
            minDate.setFullYear(today.getFullYear() - 120);
            minDate.setHours(0, 0, 0, 0);

            const errorElement = document.createElement('div');
            errorElement.className = 'error-message';
            errorElement.id = 'dob-error';

            // Remove any existing error message
            const existingError = document.getElementById('dob-error');
            if (existingError) existingError.remove();

            if (selectedDate > today) {
                errorElement.textContent = 'Date of birth cannot be in the future';
                dobInput.parentNode.appendChild(errorElement);
                dobInput.setCustomValidity('Date of birth cannot be in the future');
            } else if (selectedDate < minDate) {
                errorElement.textContent = 'Age cannot be more than 120 years';
                dobInput.parentNode.appendChild(errorElement);
                dobInput.setCustomValidity('Age cannot be more than 120 years');
            } else {
                dobInput.setCustomValidity('');
            }
        });
    </script>
</body>

</html>