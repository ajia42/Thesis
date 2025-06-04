<?php
session_start();
include("../db_config.php");

// Check if patient is logged in
if (!isset($_SESSION['patient_id'])) {
    header("Location: patient_login.php");
    exit();
}

$patient_id = $_SESSION['patient_id'];
$patient_name = $_SESSION['patient_name'];

$errors = [];
$success = '';

// Fetch available services from database
$services = [];
$sql = "SELECT service_type_id, service_name FROM service_type";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $services[$row['service_type_id']] = $row['service_name'];
    }
}

// Function to generate next appointment ID
function generateAppointmentID($conn)
{
    $sql = "SELECT MAX(appointment_id) AS max_id FROM appointment";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    // If no appointment exists, start with A0001
    if (empty($row['max_id'])) {
        return 'A0001';
    }

    // Extract the numeric part and increment
    $lastID = $row['max_id'];
    $numPart = intval(substr($lastID, 1));
    $newNumPart = $numPart + 1;

    // Format the new ID with leading zeros
    return 'A' . str_pad($newNumPart, 4, '0', STR_PAD_LEFT);
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $service_type_id = mysqli_real_escape_string($conn, $_POST['service_type_id']);
    $booking_date = mysqli_real_escape_string($conn, $_POST['booking_date']);
    $booking_time = mysqli_real_escape_string($conn, $_POST['booking_time']);
    $symptoms = isset($_POST['symptoms']) ? mysqli_real_escape_string($conn, $_POST['symptoms']) : '';

    // Validation
    if (empty($service_type_id)) $errors[] = "Service is required";
    if (empty($booking_date)) $errors[] = "Booking date is required";
    if (empty($booking_time)) $errors[] = "Booking time is required";

    // Date validation
    $today = date('Y-m-d');
    if ($booking_date < $today) {
        $errors[] = "Booking date cannot be in the past";
    }

    // Check if the selected time slot is available
    $check_sql = "SELECT * FROM appointment 
                 WHERE booking_date = '$booking_date' 
                 AND booking_time = '$booking_time'
                 AND status != 'Cancelled'";
    $check_result = mysqli_query($conn, $check_sql);
    if (mysqli_num_rows($check_result) > 0) {
        $errors[] = "The selected time slot is already booked. Please choose another time.";
    }

    if (empty($errors)) {
        $appointment_id = generateAppointmentID($conn);

        // Default admin_id (can be NULL or assign to a specific admin)
        $admin_id = NULL;

        // Default status
        $status = 'Pending';

        $sql = "INSERT INTO appointment (appointment_id, patient_id, admin_id, service_type_id, 
                booking_time, booking_date, symptoms, status) 
                VALUES ('$appointment_id', '$patient_id', NULL, '$service_type_id', 
                '$booking_time', '$booking_date', '$symptoms', '$status')";

        if (mysqli_query($conn, $sql)) {
            $success = "Appointment booked successfully!";
            // Clear form or redirect to appointments list
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
    <title>Book Appointment - Vision Care</title>
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

        /* Appointment container styles */
        .appointment-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 0 15px;
            box-sizing: border-box;
        }

        .welcome-message {
            margin-bottom: 20px;
        }

        .welcome-message h1 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .appointment-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 30px;
        }

        /* Form styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group select,
        .form-group input[type="date"],
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            box-sizing: border-box;
        }

        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }

        /* Time slots grid */
        .time-slots {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-top: 10px;
        }

        .time-slot {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }

        .time-slot input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .time-slot:hover {
            background-color: #f0f0f0;
        }

        .time-slot.selected {
            background-color: #3498db;
            color: white;
            border-color: #3498db;
        }

        /* Button styles */
        .submit-btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 14px 20px;
            width: 100%;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.2s;
            font-weight: 500;
        }

        .submit-btn:hover {
            background-color: #2980b9;
        }

        /* Message styles */
        .error-message {
            color: #e74c3c;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #fdecea;
            border-radius: 4px;
        }

        .success-message {
            color: #27ae60;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #e8f5e9;
            border-radius: 4px;
        }

        /* Responsive adjustments */
        @media (min-width: 600px) {
            .appointment-container {
                padding: 20px;
            }

            .time-slots {
                grid-template-columns: repeat(3, 1fr);
            }

            .submit-btn {
                width: auto;
                padding: 12px 30px;
            }
        }

        @media (min-width: 768px) {
            .appointment-card {
                padding: 30px;
            }

            .welcome-message h1 {
                font-size: 1.8rem;
            }
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
                <span>Welcome, <?php echo htmlspecialchars($patient_name); ?></span>
                <a href="patient_logout.php">Logout</a>
            </div>
        </div>
    </header>

    <main class="container appointment-container">
        <div class="welcome-message">
            <h1>Book an Appointment</h1>
            <p>Please fill in the details below to schedule your appointment.</p>
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

        <div class="appointment-card">
            <form method="POST" action="patient_appointment.php">
                <div class="form-group">
                    <label for="service_type_id">Service</label>
                    <select id="service_type_id" name="service_type_id" required>
                        <option value="">-- Select a service --</option>
                        <?php foreach ($services as $id => $name): ?>
                            <option value="<?php echo htmlspecialchars($id); ?>"
                                <?php echo (isset($_POST['service_type_id']) && $_POST['service_type_id'] == $id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="booking_date">Booking Date</label>
                    <input type="date" id="booking_date" name="booking_date"
                        min="<?php echo date('Y-m-d'); ?>"
                        value="<?php echo htmlspecialchars($_POST['booking_date'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label>Booking Time</label>
                    <div class="time-slots">
                        <label class="time-slot <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '08:00:00') ? 'selected' : ''; ?>">
                            <input type="radio" name="booking_time" value="08:00:00"
                                <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '08:00:00') ? 'checked' : ''; ?> required>
                            8:00 AM
                        </label>
                        <label class="time-slot <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '09:00:00') ? 'selected' : ''; ?>">
                            <input type="radio" name="booking_time" value="09:00:00"
                                <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '09:00:00') ? 'checked' : ''; ?>>
                            9:00 AM
                        </label>
                        <label class="time-slot <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '10:00:00') ? 'selected' : ''; ?>">
                            <input type="radio" name="booking_time" value="10:00:00"
                                <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '10:00:00') ? 'checked' : ''; ?>>
                            10:00 AM
                        </label>
                        <label class="time-slot <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '13:00:00') ? 'selected' : ''; ?>">
                            <input type="radio" name="booking_time" value="13:00:00"
                                <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '13:00:00') ? 'checked' : ''; ?>>
                            1:00 PM
                        </label>
                        <label class="time-slot <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '14:00:00') ? 'selected' : ''; ?>">
                            <input type="radio" name="booking_time" value="14:00:00"
                                <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '14:00:00') ? 'checked' : ''; ?>>
                            2:00 PM
                        </label>
                        <label class="time-slot <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '15:00:00') ? 'selected' : ''; ?>">
                            <input type="radio" name="booking_time" value="15:00:00"
                                <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '15:00:00') ? 'checked' : ''; ?>>
                            3:00 PM
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="symptoms">Symptoms (Optional)</label>
                    <textarea id="symptoms" name="symptoms" maxlength="30"
                        placeholder="Briefly describe your symptoms (max 30 characters)"><?php echo htmlspecialchars($_POST['symptoms'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="submit-btn">Book Appointment</button>
            </form>
        </div>
    </main>

    <script>
        // Highlight selected time slot
        document.querySelectorAll('.time-slot').forEach(slot => {
            slot.addEventListener('click', function() {
                // Remove selected class from all slots
                document.querySelectorAll('.time-slot').forEach(s => {
                    s.classList.remove('selected');
                });

                // Add selected class to clicked slot
                this.classList.add('selected');

                // Ensure the radio button is checked
                const radio = this.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                }
            });
        });

        // Date validation
        document.getElementById('booking_date').addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (selectedDate < today) {
                alert('Booking date cannot be in the past');
                this.value = '';
            }
        });
    </script>
</body>

</html>