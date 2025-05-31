<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DocTime - Register</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #7dd3fc 0%, #0ea5e9 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 500px;
            position: relative;
            overflow: hidden;
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #7dd3fc, #0ea5e9, #f97316);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #7dd3fc, #0ea5e9);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .logo::before {
            content: '+';
            color: white;
            font-size: 32px;
            font-weight: bold;
        }

        .logo::after {
            content: '';
            position: absolute;
            top: -10px;
            right: -10px;
            width: 25px;
            height: 25px;
            background: #f97316;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .title {
            font-size: 28px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #64748b;
            font-size: 16px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-row .form-group {
            flex: 1;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 500;
            font-size: 14px;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="tel"],
        select,
        textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f9fafb;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus,
        input[type="tel"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #0ea5e9;
            background: white;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
        }

        select {
            cursor: pointer;
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .gender-group {
            display: flex;
            gap: 20px;
            margin-top: 8px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .radio-option input[type="radio"] {
            width: 20px;
            height: 20px;
            accent-color: #0ea5e9;
        }

        .radio-option label {
            margin: 0;
            cursor: pointer;
        }

        .submit-btn {
            width: 100%;
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 16px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .submit-btn:hover {
            background: linear-gradient(135deg, #0284c7, #0369a1);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(14, 165, 233, 0.3);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .login-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .login-link p {
            color: #64748b;
            font-size: 16px;
        }

        .login-link a {
            color: #0ea5e9;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: #0284c7;
            text-decoration: underline;
        }

        .error {
            color: #ef4444;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }

        .success {
            background: #10b981;
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
        }

        @media (max-width: 600px) {
            .container {
                padding: 30px 20px;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .title {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo"></div>
            <h1 class="title">Join DocTime</h1>
            <p class="subtitle">Create your account to get started</p>
        </div>

        <div id="success-message" class="success"></div>

        <form id="registerForm" method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="name">First Name *</label>
                    <input type="text" id="name" name="name" required>
                    <div class="error" id="name-error"></div>
                </div>
                <div class="form-group">
                    <label for="surname">Last Name *</label>
                    <input type="text" id="surname" name="surname" required>
                    <div class="error" id="surname-error"></div>
                </div>
            </div>

            <div class="form-group">
                <label>Gender *</label>
                <div class="gender-group">
                    <div class="radio-option">
                        <input type="radio" id="male" name="gender" value="male" required>
                        <label for="male">Male</label>
                    </div>
                    <div class="radio-option">
                        <input type="radio" id="female" name="gender" value="female" required>
                        <label for="female">Female</label>
                    </div>
                    <div class="radio-option">
                        <input type="radio" id="other" name="gender" value="other" required>
                        <label for="other">Other</label>
                    </div>
                </div>
                <div class="error" id="gender-error"></div>
            </div>

            <div class="form-group">
                <label for="address">Address *</label>
                <textarea id="address" name="address" placeholder="Enter your full address" required></textarea>
                <div class="error" id="address-error"></div>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number *</label>
                <input type="tel" id="phone" name="phone" placeholder="+856 (020) 523-4567" required>
                <div class="error" id="phone-error"></div>
            </div>

            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" placeholder="your.email@example.com" required>
                <div class="error" id="email-error"></div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required>
                    <div class="error" id="password-error"></div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <div class="error" id="confirm_password-error"></div>
                </div>
            </div>

            <button type="submit" class="submit-btn">Create Account</button>
        </form>

       <div class="login-link">
    <p>Already have an account? <a href="login.php">Sign In</a></p>
</div>

    </div>

    <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Clear previous errors
            document.querySelectorAll('.error').forEach(error => {
                error.style.display = 'none';
                error.textContent = '';
            });

            let isValid = true;

            // Validate name
            const name = document.getElementById('name').value.trim();
            if (name.length < 2) {
                showError('name-error', 'First name must be at least 2 characters long');
                isValid = false;
            }

            // Validate surname
            const surname = document.getElementById('surname').value.trim();
            if (surname.length < 2) {
                showError('surname-error', 'Last name must be at least 2 characters long');
                isValid = false;
            }

            // Validate gender
            const gender = document.querySelector('input[name="gender"]:checked');
            if (!gender) {
                showError('gender-error', 'Please select your gender');
                isValid = false;
            }

            // Validate address
            const address = document.getElementById('address').value.trim();
            if (address.length < 10) {
                showError('address-error', 'Please provide a complete address');
                isValid = false;
            }

            // Validate phone
            const phone = document.getElementById('phone').value.trim();
            const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
            if (!phoneRegex.test(phone.replace(/[\s\-\(\)]/g, ''))) {
                showError('phone-error', 'Please enter a valid phone number');
                isValid = false;
            }

            // Validate email
            const email = document.getElementById('email').value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showError('email-error', 'Please enter a valid email address');
                isValid = false;
            }

            // Validate password
            const password = document.getElementById('password').value;
            if (password.length < 8) {
                showError('password-error', 'Password must be at least 8 characters long');
                isValid = false;
            }

            // Validate confirm password
            const confirmPassword = document.getElementById('confirm_password').value;
            if (password !== confirmPassword) {
                showError('confirm_password-error', 'Passwords do not match');
                isValid = false;
            }

            if (isValid) {
                // Show success message
                const successMessage = document.getElementById('success-message');
                successMessage.textContent = 'Registration successful! Welcome to DocTime!';
                successMessage.style.display = 'block';
                
                // Reset form
                document.getElementById('registerForm').reset();
                
                // Scroll to top
                window.scrollTo(0, 0);
            }
        });

        function showError(elementId, message) {
            const errorElement = document.getElementById(elementId);
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }

        // Format phone number as user types
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 10) {
                value = value.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
            }
            e.target.value = value;
        });

        // Handle navigation to login page
        function goToLogin() {
            // You can redirect to your actual login page here
            alert('Redirecting to Sign In page...\n\nIn a real application, this would navigate to the login page.');
            // window.location.href = 'login.php'; // Uncomment and update with your login page URL
        }
    </script>

    <?php
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Get form data
        $name = trim($_POST['name']);
        $surname = trim($_POST['surname']);
        $gender = $_POST['gender'];
        $address = trim($_POST['address']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Server-side validation
        $errors = [];
        
        if (strlen($name) < 2) {
            $errors[] = "First name must be at least 2 characters long";
        }
        
        if (strlen($surname) < 2) {
            $errors[] = "Last name must be at least 2 characters long";
        }
        
        if (!in_array($gender, ['male', 'female', 'other'])) {
            $errors[] = "Please select a valid gender";
        }
        
        if (strlen($address) < 10) {
            $errors[] = "Please provide a complete address";
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address";
        }
        
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long";
        }
        
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match";
        }
        
        if (empty($errors)) {
            // Hash password for security
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Here you would typically save to database
            // For demonstration, we'll just show success
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    const successMessage = document.getElementById('success-message');
                    successMessage.textContent = 'Registration successful! Welcome to DocTime, " . htmlspecialchars($name) . "!';
                    successMessage.style.display = 'block';
                    document.getElementById('registerForm').reset();
                });
            </script>";
            
            /* 
            Example database insertion (uncomment and modify as needed):
            
            $conn = new mysqli("localhost", "username", "password", "database");
            
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
            
            $stmt = $conn->prepare("INSERT INTO users (name, surname, gender, address, phone, email, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $name, $surname, $gender, $address, $phone, $email, $hashed_password);
            
            if ($stmt->execute()) {
                echo "<script>alert('Registration successful!');</script>";
            } else {
                echo "<script>alert('Registration failed. Please try again.');</script>";
            }
            
            $stmt->close();
            $conn->close();
            */
        } else {
            // Display errors
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    alert('" . implode("\\n", $errors) . "');
                });
            </script>";
        }
    }
    ?>
</body>
</html>