<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Appointment Booking</title>
    <style>
        :root {
            --primary-color: #4EADBE;
            --text-color: #333;
            --bg-color: #F5F5F5;
            --card-color: #F1F7F8;
            --white: #FFFFFF;
            --border-radius: 16px;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            min-height: 100vh;
            max-width: 800px;
            margin: 0 auto;
            background-color: var(--bg-color);
        }

        /* Header */
        .header {
            background: var(--white);
            padding: 12px 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header h1 {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-color);
        }

        .menu-container {
            position: relative;
        }


        .menu-btn, .profile-btn, .back-btn {
            background: none;
            border: none;
            padding: 8px;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .menu-btn:hover, .back-btn:hover {
            background-color: var(--card-color);
        }

        .profile-btn {
            background-color: var(--card-color);
            border-radius: 50%;
        }

        .profile-btn:hover {
            background-color: var(--primary-color);
            color: var(--white);
        }
        
        /* Dropdown Menu */
        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            min-width: 180px;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        .dropdown-menu.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: var(--text-color);
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.2s;
            border-radius: var(--border-radius);
            margin: 4px;
        }

        .dropdown-item:hover {
            background-color: var(--card-color);
        }

        .dropdown-item:first-child {
            margin-top: 8px;
        }

        .dropdown-item:last-child {
            margin-bottom: 8px;
            color: var(--danger-color);
        }

        .dropdown-item:last-child:hover {
            background-color: rgba(220, 53, 69, 0.1);
        }

        .dropdown-icon {
            font-size: 16px;
        }

        .hamburger {
            width: 24px;
            height: 18px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .hamburger span {
            width: 100%;
            height: 2px;
            background: var(--text-color);
            border-radius: 1px;
        }

        /* Main Content */
        .main-content {
            padding: 16px;
        }

        .page-title {
            margin-bottom: 24px;
        }

        .page-title h2 {
            font-size: 20px;
            font-weight: bold;
            color: var(--text-color);
        }

        /* Appointment Cards */
        .appointment-card {
            background: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 16px;
            display: flex;
        }

        .date-section {
            background: var(--primary-color);
            color: var(--white);
            padding: 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 80px;
        }

        .date-number {
            font-size: 24px;
            font-weight: bold;
            line-height: 1;
        }

        .date-month {
            font-size: 12px;
            margin-top: 2px;
        }

        .appointment-content {
            flex: 1;
            padding: 16px;
        }

        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }

        .appointment-type {
            font-weight: 600;
            color: var(--text-color);
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-completed {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }

        .status-upcoming {
            background: rgba(255, 193, 7, 0.1);
            color: var(--warning-color);
        }

        .status-new {
            background: rgba(78, 173, 190, 0.1);
            color: var(--primary-color);
        }

        .clinic-name {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 12px;
        }

        .appointment-time {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-color);
            margin-bottom: 12px;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--primary-color);
            color: var(--white);
        }

        .btn-primary:hover {
            background: #3d8fa0;
        }

        .btn-danger {
            background: var(--danger-color);
            color: var(--white);
        }

        .btn-danger:hover {
            background: #c82333;
        }

        /* FAB Button */
        .fab {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: var(--primary-color);
            color: var(--white);
            border: none;
            box-shadow: 0 4px 12px rgba(78, 173, 190, 0.4);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            transition: all 0.2s;
            z-index: 50;
        }

        .fab:hover {
            background: #3d8fa0;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(78, 173, 190, 0.4);
        }

        /* New Appointment Form */
        .form-section {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 24px;
        }

        .form-section h3 {
            font-size: 18px;
            font-weight: 500;
            color: var(--text-color);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.2s;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(78, 173, 190, 0.1);
        }

        .time-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 12px;
            margin-top: 12px;
        }

        .time-slot {
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: var(--card-color);
            text-align: center;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .time-slot:hover {
            background: var(--white);
        }

        .time-slot.selected {
            background: var(--primary-color);
            color: var(--white);
            border-color: var(--primary-color);
        }

        .appointment-types {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 12px;
        }

        .type-option {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: var(--card-color);
            cursor: pointer;
            transition: all 0.2s;
        }

        .type-option:hover {
            background: var(--white);
        }

        .type-option.selected {
            background: rgba(78, 173, 190, 0.1);
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .type-icon {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .book-btn {
            width: 100%;
            padding: 16px;
            border-radius: 8px;
            border: none;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .book-btn.enabled {
            background: var(--primary-color);
            color: var(--white);
        }

        .book-btn.enabled:hover {
            background: #3d8fa0;
        }

        .book-btn.disabled {
            background: #d1d5db;
            color: #9ca3af;
            cursor: not-allowed;
        }

        .form-note {
            font-size: 14px;
            color: #6b7280;
            margin-top: 8px;
        }

        .empty-state {
            text-align: center;
            padding: 48px 16px;
        }

        .empty-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 16px;
            opacity: 0.5;
        }

        .empty-title {
            font-size: 18px;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .empty-subtitle {
            color: #9ca3af;
        }

        /* Hidden class */
        .hidden {
            display: none !important;
        }

        /* Responsive Design */
        @media (max-width: 640px) {
            .container {
                max-width: 100%;
            }

            .time-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .fab {
                bottom: 16px;
                right: 16px;
            }
        }

        @media (max-width: 480px) {
            .appointment-card {
                flex-direction: column;
            }

            .date-section {
                flex-direction: row;
                justify-content: center;
                gap: 8px;
                min-width: auto;
                padding: 12px 16px;
            }

            .date-number {
                font-size: 20px;
            }

            .time-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Icons (simple CSS-based icons) */
        .icon-calendar::before { content: "📅"; }
        .icon-clock::before { content: "🕐"; }
        .icon-stethoscope::before { content: "🩺"; }
        .icon-eye::before { content: "👁️"; }
        .icon-user::before { content: "👤"; }
        .icon-plus::before { content: "+"; }
        .icon-back::before { content: "←"; }
        .icon-contact::before { content: "📞"; }
        .icon-logout::before { content: "🚪"; }

        /* Overlay for dropdown */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: transparent;
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .overlay.show {
            opacity: 1;
            visibility: visible;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Main View -->
        <div id="mainView">
            <!-- Header -->
            <div class="header">
                <div class="menu-container">
                    <button class="menu-btn" onclick="toggleMenu()">
                        <div class="hamburger">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </button>
                    <!-- Dropdown Menu -->
                    <div id="dropdownMenu" class="dropdown-menu">
                        <div class="dropdown-item" onclick="contactUs()">
                            <span class="dropdown-icon icon-contact"></span>
                            <span>Contact Us</span>
                        </div>
                        <div class="dropdown-item" onclick="logout()">
                            <span class="dropdown-icon icon-logout"></span>
                            <span>Log Out</span>
                        </div>
                    </div>
                </div>
                <h1>Medical Center</h1>
                <button class="profile-btn">
                    <span class="icon-user"></span>
                </button>
            </div>

            <!-- Main Content -->
            <div class="main-content">
                <div class="page-title">
                    <h2>Medical Appointments</h2>
                </div>

                <!-- Appointments List -->
                <div id="appointmentsList">
                    <!-- Sample appointments -->
                    <div class="appointment-card">
                        <div class="date-section">
                            <div class="date-number">30</div>
                            <div class="date-month">Mar</div>
                        </div>
                        <div class="appointment-content">
                            <div class="appointment-header">
                                <div class="appointment-type">General Check-up</div>
                                <span class="status-badge status-completed">Completed</span>
                            </div>
                            <div class="clinic-name">Dr. Johnson's Clinic</div>
                            <div class="appointment-time">14:30</div>
                            <div class="action-buttons">
                                <button class="btn btn-primary">View Details</button>
                            </div>
                        </div>
                    </div>

                    <div class="appointment-card">
                        <div class="date-section">
                            <div class="date-number">22</div>
                            <div class="date-month">Apr</div>
                        </div>
                        <div class="appointment-content">
                            <div class="appointment-header">
                                <div class="appointment-type">Eye Examination</div>
                                <span class="status-badge status-upcoming">Upcoming</span>
                            </div>
                            <div class="clinic-name">Dr. Johnson's Clinic</div>
                            <div class="appointment-time">9:15</div>
                            <div class="action-buttons">
                                <button class="btn btn-primary">View Details</button>
                                <button class="btn btn-danger">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State (hidden by default) -->
                <div id="emptyState" class="empty-state hidden">
                    <div class="empty-icon">📅</div>
                    <div class="empty-title">No Appointments</div>
                    <div class="empty-subtitle">Tap the + button to create a new appointment</div>
                </div>
            </div>

            <!-- FAB Button -->
            <button class="fab" onclick="showNewAppointment()">
                <span class="icon-plus"></span>
            </button>
        </div>

        <!-- New Appointment View -->
        <div id="newAppointmentView" class="hidden">
            <!-- Header -->
            <div class="header">
                <button class="back-btn" onclick="showMainView()">
                    <span class="icon-back"></span>
                </button>
                <h1>New Appointment</h1>
                <div style="width: 36px;"></div>
            </div>

            <!-- Form Content -->
            <div class="main-content">
                <!-- Date Selection -->
                <div class="form-section">
                    <h3>
                        <span class="icon-calendar"></span>
                        Select Date
                    </h3>
                    <input type="date" id="appointmentDate" class="form-input" min="">
                    <div class="form-note">*Cannot book for today or past dates</div>
                </div>

                <!-- Time Selection -->
                <div class="form-section">
                    <h3>
                        <span class="icon-clock"></span>
                        Select Time
                    </h3>
                    <div class="time-grid" id="timeSlots">
                        <div class="time-slot" data-time="8:00">8:00</div>
                        <div class="time-slot" data-time="9:00">9:00</div>
                        <div class="time-slot" data-time="10:00">10:00</div>
                        <div class="time-slot" data-time="13:00">1:00</div>
                        <div class="time-slot" data-time="14:00">2:00</div>
                        <div class="time-slot" data-time="15:00">3:00</div>
                    </div>
                </div>

                <!-- Appointment Type -->
                <div class="form-section">
                    <h3>Appointment Type</h3>
                    <div class="appointment-types">
                        <div class="type-option" data-type="general">
                            <div class="type-icon">
                                <span class="icon-stethoscope"></span>
                            </div>
                            <span>General Check</span>
                        </div>
                        <div class="type-option" data-type="eyes">
                            <div class="type-icon">
                                <span class="icon-eye"></span>
                            </div>
                            <span>Eyes Check</span>
                        </div>
                    </div>
                </div>

                <!-- Book Button -->
                <button id="bookBtn" class="book-btn disabled" onclick="bookAppointment()">
                    Book Appointment
                </button>
            </div>
        </div>

        <!-- Overlay for dropdown menu -->
        <div id="overlay" class="overlay" onclick="closeMenu()"></div>
    </div>

    <script>
        // Global variables
        let selectedDate = '';
        let selectedTime = '';
        let selectedType = '';
        let appointments = [];

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Set minimum date to tomorrow
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const tomorrowString = tomorrow.toISOString().split('T')[0];
            document.getElementById('appointmentDate').min = tomorrowString;

            // Add event listeners
            setupEventListeners();

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                const menuContainer = document.querySelector('.menu-container');
                if (!menuContainer.contains(e.target)) {
                    closeMenu();
                }
            });
        });

        // Menu Functions
        function toggleMenu() {
            const dropdown = document.getElementById('dropdownMenu');
            const overlay = document.getElementById('overlay');
            const isOpen = dropdown.classList.contains('show');
            
            if (isOpen) {
                closeMenu();
            } else {
                dropdown.classList.add('show');
                overlay.classList.add('show');
            }
        }

        function closeMenu() {
            document.getElementById('dropdownMenu').classList.remove('show');
            document.getElementById('overlay').classList.remove('show');
        }

        function contactUs() {
            closeMenu();
            alert('Contact Us\n\nPhone: +1 (555) 123-4567\nEmail: info@medicalcenter.com\nAddress: 123 Health Street, Medical City, MC 12345\n\nOffice Hours:\nMonday - Friday: 8:00 AM - 6:00 PM\nSaturday: 9:00 AM - 2:00 PM\nSunday: Closed');
        }

        function logout() {
            closeMenu();
            if (confirm('Are you sure you want to log out?')) {
                alert('You have been logged out successfully.');
                // Here you would typically redirect to login page
                // window.location.href = 'login.php';
            }
        }

        function setupEventListeners() {
            // Date input
            document.getElementById('appointmentDate').addEventListener('change', function(e) {
                selectedDate = e.target.value;
                updateBookButton();
            });

            // Time slots
            document.querySelectorAll('.time-slot').forEach(slot => {
                slot.addEventListener('click', function() {
                    document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
                    this.classList.add('selected');
                    selectedTime = this.dataset.time;
                    updateBookButton();
                });
            });

            // Appointment types
            document.querySelectorAll('.type-option').forEach(option => {
                option.addEventListener('click', function() {
                    document.querySelectorAll('.type-option').forEach(o => o.classList.remove('selected'));
                    this.classList.add('selected');
                    selectedType = this.dataset.type;
                    updateBookButton();
                });
            });
        }

        function updateBookButton() {
            const bookBtn = document.getElementById('bookBtn');
            if (selectedDate && selectedTime && selectedType) {
                bookBtn.classList.remove('disabled');
                bookBtn.classList.add('enabled');
            } else {
                bookBtn.classList.remove('enabled');
                bookBtn.classList.add('disabled');
            }
        }

        function showNewAppointment() {
            document.getElementById('mainView').classList.add('hidden');
            document.getElementById('newAppointmentView').classList.remove('hidden');
        }

        function showMainView() {
            document.getElementById('newAppointmentView').classList.add('hidden');
            document.getElementById('mainView').classList.remove('hidden');
            
            // Reset form
            resetForm();
        }

        function resetForm() {
            selectedDate = '';
            selectedTime = '';
            selectedType = '';
            
            document.getElementById('appointmentDate').value = '';
            document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
            document.querySelectorAll('.type-option').forEach(o => o.classList.remove('selected'));
            updateBookButton();
        }

        function bookAppointment() {
            if (!selectedDate || !selectedTime || !selectedType) {
                alert('Please select date, time and appointment type');
                return;
            }

            // Get appointment type name
            const typeNames = {
                'general': 'General Check',
                'eyes': 'Eyes Check'
            };

            // Create appointment object
            const appointmentDate = new Date(selectedDate);
            const newAppointment = {
                id: Date.now(),
                date: appointmentDate.getDate().toString(),
                month: appointmentDate.toLocaleDateString('en-US', { month: 'short' }),
                type: typeNames[selectedType],
                time: selectedTime,
                status: 'upcoming'
            };

            // Add to appointments array
            appointments.unshift(newAppointment);

            // Create and add appointment card to DOM
            addAppointmentCard(newAppointment);

            alert(`Appointment Booked Successfully!\nDate: ${selectedDate}\nTime: ${selectedTime}\nType: ${typeNames[selectedType]}`);
            
            // Return to main view
            showMainView();
        }

        function addAppointmentCard(appointment) {
            const appointmentsList = document.getElementById('appointmentsList');
            const emptyState = document.getElementById('emptyState');
            
            // Hide empty state if visible
            emptyState.classList.add('hidden');

            // Create appointment card HTML
            const cardHTML = `
                <div class="appointment-card">
                    <div class="date-section">
                        <div class="date-number">${appointment.date}</div>
                        <div class="date-month">${appointment.month}</div>
                    </div>
                    <div class="appointment-content">
                        <div class="appointment-header">
                            <div class="appointment-type">${appointment.type}</div>
                            <span class="status-badge status-new">New</span>
                        </div>
                        <div class="clinic-name">Dr. Johnson's Clinic</div>
                        <div class="appointment-time">${appointment.time}</div>
                        <div class="action-buttons">
                            <button class="btn btn-primary">View Details</button>
                            <button class="btn btn-danger" onclick="cancelAppointment(${appointment.id})">Cancel</button>
                        </div>
                    </div>
                </div>
            `;

            // Insert at the beginning of the list
            appointmentsList.insertAdjacentHTML('afterbegin', cardHTML);
        }

        function cancelAppointment(appointmentId) {
            if (confirm('Are you sure you want to cancel this appointment?')) {
                // Remove from appointments array
                appointments = appointments.filter(apt => apt.id !== appointmentId);
                
                // Remove from DOM
                const appointmentCards = document.querySelectorAll('.appointment-card');
                appointmentCards.forEach(card => {
                    const cancelBtn = card.querySelector(`button[onclick="cancelAppointment(${appointmentId})"]`);
                    if (cancelBtn) {
                        card.remove();
                    }
                });

                // Show empty state if no appointments
                const remainingCards = document.querySelectorAll('#appointmentsList .appointment-card');
                if (remainingCards.length === 0) {
                    document.getElementById('emptyState').classList.remove('hidden');
                }

                alert('Appointment cancelled successfully');
            }
        }

        // PHP-like functionality can be added here for server communication
        // Example AJAX functions:
        
        function saveAppointmentToServer(appointmentData) {
            // This would be used to send data to a PHP backend
            fetch('save_appointment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(appointmentData)
            })
            .then(response => response.json())
            .then(data => {
                console.log('Appointment saved:', data);
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        function loadAppointmentsFromServer() {
            // This would be used to load appointments from PHP backend
            fetch('get_appointments.php')
            .then(response => response.json())
            .then(data => {
                // Populate appointments list
                console.log('Appointments loaded:', data);
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html>