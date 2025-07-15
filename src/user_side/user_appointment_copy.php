<?php
session_start();
include("../db_config.php");

// Check if user is properly authenticated
if (!isset($_SESSION['registered_phone'])) {
    header("Location: user_login.php");
    exit();
}

// Get the patient record for this user
$phone = $_SESSION['registered_phone'];
$patient_query = "SELECT patient_id FROM patient WHERE phone = '$phone'";
$patient_result = mysqli_query($conn, $patient_query);

if (!$patient_result || mysqli_num_rows($patient_result) == 0) {
    // Patient doesn't exist - redirect to complete profile
    header("Location: user_info.php");
    exit();
}

$patient_row = mysqli_fetch_assoc($patient_result);
$patient_id = $patient_row['patient_id'];

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
        $status = 'scheduled'; // Default status

        $sql = "INSERT INTO appointment (appointment_id, patient_id, service_type_id, 
                booking_time, booking_date, symptoms, status) 
                VALUES ('$appointment_id', '$patient_id', '$service_type_id', 
                '$booking_time', '$booking_date', '$symptoms', '$status')";

        if (mysqli_query($conn, $sql)) {
            // Redirect to history page after successful booking
            header("Location: user_history.php");
            exit();
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
    <title>Book Appointment - Vision Care</title>
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

        .back-button {
            background-color: #f8f9fa;
            color: #212529;
            border: 1px solid #ddd;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .back-button:hover {
            background-color: #e2e6ea;
            border-color: #dae0e5;
        }

        .back-button svg {
            width: 16px;
            height: 16px;
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

        .time-slot.disabled {
            background-color: #f5f5f5;
            color: #ccc;
            cursor: not-allowed;
            border-color: #eee;
            position: relative;
        }

        .time-slot.disabled:hover {
            background-color: #f5f5f5;
        }

        .time-slot.disabled::after {
            content: "✗";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 1.2em;
            color: #e74c3c;
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

    <main class="container appointment-container">
        <a href="user_history.php" class="back-button">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"></path>
            </svg>
            Back to History
        </a>
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

        <div class="appointment-card">
            <form method="POST" action="user_appointment.php">
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
                        <label class="time-slot <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '08:00:00') ? 'selected' : ''; ?>" data-hour="8" data-minute="0">
                            <input type="radio" name="booking_time" value="08:00:00"
                                <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '08:00:00') ? 'checked' : ''; ?> required>
                            08:00
                        </label>
                        <label class="time-slot <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '08:30:00') ? 'selected' : ''; ?>" data-hour="8" data-minute="30">
                            <input type="radio" name="booking_time" value="08:30:00"
                                <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '08:30:00') ? 'checked' : ''; ?>>
                            08:30
                        </label>
                        <label class="time-slot <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '09:00:00') ? 'selected' : ''; ?>" data-hour="9" data-minute="0">
                            <input type="radio" name="booking_time" value="09:00:00"
                                <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '09:00:00') ? 'checked' : ''; ?>>
                            09:00
                        </label>
                        <label class="time-slot <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '09:30:00') ? 'selected' : ''; ?>" data-hour="9" data-minute="30">
                            <input type="radio" name="booking_time" value="09:30:00"
                                <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '09:30:00') ? 'checked' : ''; ?>>
                            09:30
                        </label>
                        <label class="time-slot <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '10:00:00') ? 'selected' : ''; ?>" data-hour="10" data-minute="0">
                            <input type="radio" name="booking_time" value="10:00:00"
                                <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '10:00:00') ? 'checked' : ''; ?>>
                            10:00
                        </label>
                        <label class="time-slot <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '10:30:00') ? 'selected' : ''; ?>" data-hour="10" data-minute="30">
                            <input type="radio" name="booking_time" value="10:30:00"
                                <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '10:30:00') ? 'checked' : ''; ?>>
                            10:30
                        </label>
                        <label class="time-slot <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '13:00:00') ? 'selected' : ''; ?>" data-hour="13" data-minute="0">
                            <input type="radio" name="booking_time" value="13:00:00"
                                <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '13:00:00') ? 'checked' : ''; ?>>
                            13:00
                        </label>
                        <label class="time-slot <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '13:30:00') ? 'selected' : ''; ?>" data-hour="13" data-minute="30">
                            <input type="radio" name="booking_time" value="13:30:00"
                                <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '13:30:00') ? 'checked' : ''; ?>>
                            13:30
                        </label>
                        <label class="time-slot <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '14:00:00') ? 'selected' : ''; ?>" data-hour="14" data-minute="0">
                            <input type="radio" name="booking_time" value="14:00:00"
                                <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '14:00:00') ? 'checked' : ''; ?>>
                            14:00
                        </label>
                        <label class="time-slot <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '14:30:00') ? 'selected' : ''; ?>" data-hour="14" data-minute="30">
                            <input type="radio" name="booking_time" value="14:30:00"
                                <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '14:30:00') ? 'checked' : ''; ?>>
                            14:30
                        </label>
                        <label class="time-slot <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '15:00:00') ? 'selected' : ''; ?>" data-hour="15" data-minute="0">
                            <input type="radio" name="booking_time" value="15:00:00"
                                <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '15:00:00') ? 'checked' : ''; ?>>
                            15:00
                        </label>
                        <label class="time-slot <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '15:30:00') ? 'selected' : ''; ?>" data-hour="15" data-minute="30">
                            <input type="radio" name="booking_time" value="15:30:00"
                                <?php echo (isset($_POST['booking_time']) && $_POST['booking_time'] == '15:30:00') ? 'checked' : ''; ?>>
                            15:30
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

        // Check available time slots when date changes
        document.getElementById('booking_date').addEventListener('change', function() {
            const selectedDate = this.value;
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const dateObj = new Date(selectedDate);

            if (dateObj < today) {
                alert('Booking date cannot be in the past');
                this.value = '';
                return;
            }

            if (selectedDate) {
                // Disable all time slots while checking
                document.querySelectorAll('.time-slot input').forEach(slot => {
                    slot.disabled = true;
                    slot.parentElement.classList.add('disabled');
                });

                // Fetch already booked time slots for this date
                // Modify the fetch response handling in both event listeners
                fetch(`check_time_slots.php?date=${selectedDate}`)
                    .then(response => response.json())
                    .then(bookedSlots => {
                        document.querySelectorAll('.time-slot input').forEach(slot => {
                            const slotValue = slot.value;
                            const isBooked = bookedSlots.includes(slotValue);

                            // Disable both the radio button and the label
                            slot.disabled = isBooked;
                            const label = slot.parentElement;
                            label.classList.toggle('disabled', isBooked);

                            // If this slot was selected but is now booked, unselect it
                            if (isBooked && slot.checked) {
                                slot.checked = false;
                                label.classList.remove('selected');
                            }
                        });

                        // After checking booked slots, disable passed slots if today
                        disablePassedTimeSlots();
                    })
            }
        });

        // Highlight selected time slot
        document.querySelectorAll('.time-slot').forEach(slot => {
            slot.addEventListener('click', function() {
                if (this.classList.contains('disabled')) return;

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

        // Function to disable passed time slots for today
        function disablePassedTimeSlots() {
            const today = new Date();
            const currentHour = today.getHours();
            const currentMinutes = today.getMinutes();

            // Only proceed if the selected date is today
            const selectedDate = new Date(document.getElementById('booking_date').value);
            const isToday = selectedDate.toDateString() === today.toDateString();

            if (!isToday) {
                // If not today, make sure all slots are enabled
                document.querySelectorAll('.time-slot').forEach(slot => {
                    const radio = slot.querySelector('input[type="radio"]');
                    if (!slot.classList.contains('disabled')) {
                        radio.disabled = false;
                    }
                });
                return;
            }

            document.querySelectorAll('.time-slot').forEach(slot => {
                const hour = parseInt(slot.dataset.hour);
                const minute = parseInt(slot.dataset.minute);

                // If the time has already passed today
                if (hour < currentHour || (hour === currentHour && minute < currentMinutes)) {
                    const radio = slot.querySelector('input[type="radio"]');
                    radio.disabled = true;
                    slot.classList.add('disabled');
                } else {
                    // Enable slots that haven't passed yet (unless they're booked)
                    const radio = slot.querySelector('input[type="radio"]');
                    if (!slot.classList.contains('disabled')) {
                        radio.disabled = false;
                    }
                }
            });
        }

        // Call this function when date changes, after checking booked slots
        document.getElementById('booking_date').addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (selectedDate < today) {
                alert('Booking date cannot be in the past');
                this.value = '';
                return;
            }

            if (this.value) {
                // First disable all slots while checking
                document.querySelectorAll('.time-slot input').forEach(slot => {
                    slot.disabled = true;
                    slot.parentElement.classList.add('disabled');
                });

                // Fetch already booked time slots for this date
                fetch(`check_time_slots.php?date=${this.value}`)
                    .then(response => response.json())
                    .then(bookedSlots => {
                        document.querySelectorAll('.time-slot input').forEach(slot => {
                            const slotValue = slot.value;
                            const isBooked = bookedSlots.includes(slotValue);

                            slot.disabled = isBooked;
                            slot.parentElement.classList.toggle('disabled', isBooked);
                        });

                        // After checking booked slots, disable passed slots if today
                        disablePassedTimeSlots();
                    })
                    .catch(error => {
                        console.error('Error checking time slots:', error);
                        // Re-enable all slots if there's an error
                        document.querySelectorAll('.time-slot input').forEach(slot => {
                            slot.disabled = false;
                            slot.parentElement.classList.remove('disabled');
                        });
                        disablePassedTimeSlots();
                    });
            }
        });

        // Also call it when the page loads if the selected date is today
        // Modify the DOMContentLoaded event listener
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const selectedDate = document.getElementById('booking_date').value;

            if (selectedDate) {
                // First disable all slots while checking
                document.querySelectorAll('.time-slot input').forEach(slot => {
                    slot.disabled = true;
                    slot.parentElement.classList.add('disabled');
                });

                // Fetch already booked time slots for the selected date
                fetch(`check_time_slots.php?date=${selectedDate}`)
                    .then(response => response.json())
                    .then(bookedSlots => {
                        document.querySelectorAll('.time-slot input').forEach(slot => {
                            const slotValue = slot.value;
                            const isBooked = bookedSlots.includes(slotValue);

                            slot.disabled = isBooked;
                            slot.parentElement.classList.toggle('disabled', isBooked);

                            // If this slot was selected but is now booked, unselect it
                            if (isBooked && slot.checked) {
                                slot.checked = false;
                                slot.parentElement.classList.remove('selected');
                            }
                        });

                        // After checking booked slots, disable passed slots if today
                        if (selectedDate === today) {
                            disablePassedTimeSlots();
                        }
                    })
                    .catch(error => {
                        console.error('Error checking time slots:', error);
                        // Re-enable all slots if there's an error
                        document.querySelectorAll('.time-slot input').forEach(slot => {
                            slot.disabled = false;
                            slot.parentElement.classList.remove('disabled');
                        });
                        if (selectedDate === today) {
                            disablePassedTimeSlots();
                        }
                    });
            }
        });
    </script>
</body>

</html>