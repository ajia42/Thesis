<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .profile-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            padding: 30px;
            position: relative;
            overflow: hidden;
        }

        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            width: 40px;
            height: 40px;
            background: #f5f5f5;
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #666;
            font-size: 18px;
            z-index: 10;
        }

        .back-button:hover {
            background: #e0e0e0;
            transform: scale(1.1);
            color: #333;
        }

        .back-button:active {
            transform: scale(0.95);
        }

        .profile-avatar {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            position: relative;
        }

        .avatar-circle {
            width: 80px;
            height: 80px;
            background: #e0e0e0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .avatar-circle:hover {
            background: #d0d0d0;
            transform: scale(1.05);
        }

        .avatar-icon {
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .avatar-icon::before {
            content: '👤';
            font-size: 20px;
            color: #999;
        }

        .avatar-plus {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 25px;
            height: 25px;
            background: #4CAF50;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            font-weight: bold;
            border: 3px solid white;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }

        .form-input {
            width: 100%;
            padding: 15px;
            border: 2px solid #f0f0f0;
            border-radius: 12px;
            font-size: 16px;
            background: #fafafa;
            transition: all 0.3s ease;
            outline: none;
        }

        .form-input:focus {
            border-color: #00bcd4;
            background: white;
            box-shadow: 0 0 0 4px rgba(0, 188, 212, 0.1);
        }

        .phone-input-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .country-code {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 15px 12px;
            border: 2px solid #f0f0f0;
            border-radius: 12px;
            background: #fafafa;
            min-width: 80px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .country-code:hover {
            border-color: #e0e0e0;
        }

        .flag {
            font-size: 18px;
        }

        .phone-input {
            flex: 1;
        }

        .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 18px;
        }

        .btn-primary {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #00bcd4 0%, #00acc1 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #00acc1 0%, #0097a7 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 188, 212, 0.3);
        }

        .btn-secondary {
            width: 100%;
            padding: 16px;
            background: transparent;
            color: #666;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: #f5f5f5;
            color: #333;
        }

        .success-message {
            background: #e8f5e8;
            color: #2e7d32;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            display: none;
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            display: none;
        }

        @media (max-width: 480px) {
            .profile-container {
                padding: 20px;
                margin: 10px;
            }

            .form-input {
                font-size: 16px;
                /* Prevents zoom on iOS */
            }
        }

        @media (max-width: 360px) {
            .phone-input-container {
                flex-direction: column;
                gap: 15px;
            }

            .country-code {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <div class="profile-container">
        <button class="back-button" onclick="goBack()" title="Go Back">
            ←
        </button>

        <div id="successMessage" class="success-message">
            Profile updated successfully!
        </div>

        <div id="errorMessage" class="error-message">
            Please fill in all required fields.
        </div>

        <form id="profileForm" method="POST" action="" enctype="multipart/form-data">
            <div class="profile-avatar">
                <div class="avatar-circle" onclick="document.getElementById('avatarInput').click()">
                    <div class="avatar-icon"></div>

                </div>
                <input type="file" id="avatarInput" name="avatar" accept="image/*" style="display: none;">
            </div>

            <div class="form-group">
                <label class="form-label" for="fullName">Full Name</label>
                <input type="text" id="fullName" name="full_name" class="form-input" placeholder="Your Name" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-input" placeholder="example@your-email" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="phone">Phone Number</label>
                <div class="phone-input-container">
                    <input type="tel" id="phone" name="phone" class="form-input phone-input" placeholder="5678 9101" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="address">Home Address</label>
                <textarea id="address" name="address" class="form-input" placeholder="Your Address" rows="3" style="resize: vertical;"></textarea>
            </div>

            <button type="submit" class="btn-primary">Update Profile</button>
            <button type="button" class="btn-secondary" onclick="resetForm()">Cancel</button>
        </form>
    </div>

    <script>
        // Form validation and submission
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const fullName = document.getElementById('fullName').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone').value.trim();

            // Hide previous messages
            document.getElementById('successMessage').style.display = 'none';
            document.getElementById('errorMessage').style.display = 'none';

            // Basic validation
            if (!fullName || !email || !phone) {
                document.getElementById('errorMessage').style.display = 'block';
                return;
            }

            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                document.getElementById('errorMessage').textContent = 'Please enter a valid email address.';
                document.getElementById('errorMessage').style.display = 'block';
                return;
            }

            // Phone validation (basic)
            const phoneRegex = /^[\d\s\-\+\(\)]+$/;
            if (!phoneRegex.test(phone)) {
                document.getElementById('errorMessage').textContent = 'Please enter a valid phone number.';
                document.getElementById('errorMessage').style.display = 'block';
                return;
            }

            // Simulate successful submission
            document.getElementById('successMessage').style.display = 'block';

            // In a real application, you would submit the form data to your PHP backend
            // For demo purposes, we'll just show success message
            console.log('Form data:', {
                fullName: fullName,
                email: email,
                phone: phone,
                address: document.getElementById('address').value.trim()
            });
        });

        // Avatar upload preview
        document.getElementById('avatarInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const avatarIcon = document.querySelector('.avatar-icon');
                    avatarIcon.style.backgroundImage = `url(${e.target.result})`;
                    avatarIcon.style.backgroundSize = 'cover';
                    avatarIcon.style.backgroundPosition = 'center';
                    avatarIcon.innerHTML = '';
                };
                reader.readAsDataURL(file);
            }
        });

        // Reset form function
        function resetForm() {
            document.getElementById('profileForm').reset();
            document.getElementById('successMessage').style.display = 'none';
            document.getElementById('errorMessage').style.display = 'none';

            // Reset avatar
            const avatarIcon = document.querySelector('.avatar-icon');
            avatarIcon.style.backgroundImage = '';
            avatarIcon.innerHTML = '';
        }

        // Back button function
        function goBack() {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                // Fallback if no history
                window.location.href = '/home'; // Change to your desired fallback page
            }
        }

        // Phone number formatting
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            let formattedValue = '';

            if (value.length > 0) {
                if (value.length <= 4) {
                    formattedValue = value;
                } else if (value.length <= 8) {
                    formattedValue = value.slice(0, 4) + ' ' + value.slice(4);
                } else {
                    formattedValue = value.slice(0, 4) + ' ' + value.slice(4, 8) + ' ' + value.slice(8, 12);
                }
            }

            e.target.value = formattedValue;
        });
    </script>

    <?php
    // PHP Backend Processing
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Sanitize and validate input
        $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $address = isset($_POST['address']) ? trim($_POST['address']) : '';

        $errors = [];

        // Validation
        if (empty($full_name)) {
            $errors[] = "Full name is required.";
        }

        if (empty($email)) {
            $errors[] = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }

        if (empty($phone)) {
            $errors[] = "Phone number is required.";
        }

        // Handle avatar upload
        $avatar_path = '';
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/avatars/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array(strtolower($file_extension), $allowed_types)) {
                $avatar_filename = uniqid() . '.' . $file_extension;
                $avatar_path = $upload_dir . $avatar_filename;

                if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $avatar_path)) {
                    $errors[] = "Failed to upload avatar.";
                }
            } else {
                $errors[] = "Invalid file type for avatar.";
            }
        }

        if (empty($errors)) {
            // Here you would typically save to database
            // For demonstration, we'll just log the data

            $profile_data = [
                'full_name' => $full_name,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'avatar_path' => $avatar_path,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Example database insertion (uncomment and modify as needed)
            /*
            try {
                $pdo = new PDO("mysql:host=localhost;dbname=your_database", $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, avatar_path = ?, updated_at = ? WHERE id = ?");
                $stmt->execute([$full_name, $email, $phone, $address, $avatar_path, date('Y-m-d H:i:s'), $user_id]);
                
                $success_message = "Profile updated successfully!";
            } catch(PDOException $e) {
                $errors[] = "Database error: " . $e->getMessage();
            }
            */

            // For demo purposes, just show success
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    document.getElementById('successMessage').style.display = 'block';
                });
            </script>";
        } else {
            // Display errors
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    document.getElementById('errorMessage').textContent = '" . implode(' ', $errors) . "';
                    document.getElementById('errorMessage').style.display = 'block';
                });
            </script>";
        }
    }
    ?>
</body>

</html>