<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DocTime - Medical Appointment Booking</title>
    <style>
        :root {
            --primary-color: #5bb0f7;
            --secondary-color: #79e2e2;
            --accent-color: #ef5350;
            --text-dark: #333333;
            --text-light: #ffffff;
            --background-light: #f5f5f5;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: var(--background-light);
            color: var(--text-dark);
            min-height: 100vh;
        }

        /* Splash Screen */
        .splash-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background: linear-gradient(to bottom, var(--secondary-color), var(--primary-color));
            text-align: center;
            padding: 20px;
        }

        .logo-container {
            position: relative;
            width: 120px;
            height: 120px;
            margin-bottom: 30px;
        }

        .logo {
            width: 100%;
            height: 100%;
            background: var(--text-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow);
        }

        .logo svg {
            width: 60%;
            height: 60%;
            fill: var(--primary-color);
        }

        .icon {
            position: absolute;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow);
        }

        .icon svg {
            width: 60%;
            height: 60%;
        }

        .icon-1 {
            background: var(--text-light);
            top: -10px;
            right: -10px;
        }

        .icon-2 {
            background: var(--primary-color);
            bottom: 0;
            left: -10px;
        }

        .icon-3 {
            background: var(--accent-color);
            top: 20px;
            right: -20px;
        }

        .splash-text {
            color: var(--text-light);
            margin-bottom: 40px;
        }

        .splash-text h1 {
            font-size: 24px;
            margin-bottom: 8px;
        }

        .splash-text h2 {
            font-size: 32px;
            font-weight: bold;
        }

        .get-started-btn {
            background: var(--text-light);
            color: var(--primary-color);
            border: none;
            padding: 15px 40px;
            border-radius: 30px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease;
        }

        .get-started-btn:hover {
            transform: scale(1.05);
        }

        /* Main App */
        .app-container {
            display: none;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            margin-bottom: 30px;
        }

        .header-logo {
            display: flex;
            align-items: center;
        }

        .header-logo svg {
            width: 40px;
            height: 40px;
            margin-right: 10px;
        }

        .header-logo h1 {
            font-size: 24px;
            color: var(--primary-color);
        }

        .profile-btn {
            background: var(--primary-color);
            color: var(--text-light);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .profile-btn svg {
            width: 20px;
            height: 20px;
        }

        .search-container {
            position: relative;
            margin-bottom: 30px;
        }

        .search-input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: none;
            border-radius: 10px;
            background: var(--text-light);
            box-shadow: var(--shadow);
            font-size: 16px;
        }

        .search-icon {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            color: #999;
        }

        .categories {
            display: flex;
            gap: 15px;
            overflow-x: auto;
            padding: 10px 0;
            margin-bottom: 30px;
            scrollbar-width: none;
        }

        .categories::-webkit-scrollbar {
            display: none;
        }

        .category {
            background: var(--text-light);
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 14px;
            white-space: nowrap;
            cursor: pointer;
            box-shadow: var(--shadow);
        }

        .category.active {
            background: var(--primary-color);
            color: var(--text-light);
        }

        .top-doctors-section {
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .section-title {
            font-size: 20px;
            font-weight: bold;
        }

        .see-all {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: bold;
        }

        .doctors-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .doctor-card {
            background: var(--text-light);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .doctor-img {
            height: 150px;
            background: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .doctor-img img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }

        .doctor-info {
            padding: 15px;
        }

        .doctor-name {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .doctor-specialty {
            color: #777;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .rating {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .rating svg {
            color: #ffc107;
            margin-right: 5px;
        }

        .doctor-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .price {
            color: var(--primary-color);
            font-weight: bold;
        }

        .book-btn {
            background: var(--primary-color);
            color: var(--text-light);
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            cursor: pointer;
        }

        .upcoming-appointments {
            margin-bottom: 30px;
        }

        .appointment-card {
            background: var(--text-light);
            border-radius: 15px;
            padding: 20px;
            box-shadow: var(--shadow);
            margin-bottom: 15px;
        }

        .appointment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .appointment-doctor {
            display: flex;
            align-items: center;
        }

        .appointment-doctor-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #eee;
            margin-right: 15px;
            overflow: hidden;
        }

        .appointment-doctor-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .appointment-type {
            background: rgba(91, 176, 247, 0.1);
            color: var(--primary-color);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
        }

        .appointment-time {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .time-item {
            display: flex;
            align-items: center;
        }

        .time-item svg {
            margin-right: 5px;
            color: #777;
        }

        .time-info {
            font-size: 14px;
        }

        .time-info span {
            color: #777;
        }

        .appointment-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .appointment-btn {
            flex: 1;
            padding: 10px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
        }

        .cancel-btn {
            background: rgba(239, 83, 80, 0.1);
            color: var(--accent-color);
            border: 1px solid var(--accent-color);
        }

        .reschedule-btn {
            background: rgba(91, 176, 247, 0.1);
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }

        /* Navigation */
        nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--text-light);
            display: flex;
            justify-content: space-around;
            padding: 15px 0;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            z-index: 100;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #999;
            text-decoration: none;
        }

        .nav-item.active {
            color: var(--primary-color);
        }

        .nav-item svg {
            margin-bottom: 5px;
        }

        .nav-text {
            font-size: 12px;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .doctors-list {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }

        @media (max-width: 576px) {
            .doctors-list {
                grid-template-columns: 1fr;
            }
            
            .appointment-header {
                flex-direction: column;
            }
            
            .appointment-doctor {
                margin-bottom: 10px;
            }
            
            .appointment-time {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .time-item {
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Splash Screen -->
    <div class="splash-container" id="splash-screen">
        <div class="logo-container">
            <div class="logo">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M10.5 17h3v-2.5H16v-3h-2.5V9h-3v2.5H8v3h2.5zm1.5 5C6.48 22 2 17.52 2 12S6.48 2 12 2s10 4.48 10 10-4.48 10-10 10zm0-18c-4.41 0-8 3.59-8 8s3.59 8 8 8 8-3.59 8-8-3.59-8-8-8z"/>
                </svg>
            </div>
            <div class="icon icon-1">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#5bb0f7">
                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-8.5 12H9v-1.5H7.5v-3H9V9h1.5v1.5h3V12h-1.5v1.5h-1.5V15z"/>
                </svg>
            </div>
            <div class="icon icon-2">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#ffffff">
                    <path d="M17.5 12.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2zM14 11a1 1 0 1 0 0 2 1 1 0 0 0 0-2zm-3.5 1.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2zM10 11a1 1 0 1 0 0 2 1 1 0 0 0 0-2zm-3.5 1.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                    <path d="M20 5h-4V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v1H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2zM10 4h4v1h-4V4zm10 13H4V7h16v10z"/>
                </svg>
            </div>
            <div class="icon icon-3">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#ffffff">
                    <path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 14H4V8l8 5 8-5v10zm-8-7L4 6h16l-8 5z"/>
                </svg>
            </div>
        </div>
        <div class="splash-text">
            <h1>WELCOME TO</h1>
            <h2>DocTime</h2>
        </div>
       <button class="get-started-btn" id="get-started-btn" onclick="window.location.href='login.php'">Get Started</button>

    </div>

    <!-- Main App -->
    <div class="app-container" id="app-container">
        <header>
            <div class="header-logo">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#5bb0f7">
                    <path d="M10.5 17h3v-2.5H16v-3h-2.5V9h-3v2.5H8v3h2.5zm1.5 5C6.48 22 2 17.52 2 12S6.48 2 12 2s10 4.48 10 10-4.48 10-10 10zm0-18c-4.41 0-8 3.59-8 8s3.59 8 8 8 8-3.59 8-8-3.59-8-8-8z"/>
                </svg>
                <h1>DocTime</h1>
            </div>
            <button class="profile-btn">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#ffffff">
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
            </button>
        </header>

        <div class="search-container">
            <input type="text" class="search-input" placeholder="Search doctors, specialists...">
            <span class="search-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#999">
                    <path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                </svg>
            </span>
        </div>

        <div class="categories">
            <button class="category active">All</button>
            <button class="category">General</button>
            <button class="category">Dentist</button>
            <button class="category">Cardiologist</button>
            <button class="category">Neurologist</button>
            <button class="category">Dermatologist</button>
            <button class="category">Pediatrician</button>
        </div>

        <section class="top-doctors-section">
            <div class="section-header">
                <h2 class="section-title">Top Doctors</h2>
                <a href="#" class="see-all">See All</a>
            </div>
            <div class="doctors-list">
                <div class="doctor-card">
                    <div class="doctor-img">
                        <img src="/api/placeholder/200/200" alt="Doctor avatar">
                    </div>
                    <div class="doctor-info">
                        <h3 class="doctor-name">Dr. Sarah Johnson</h3>
                        <p class="doctor-specialty">Cardiologist</p>
                        <div class="rating">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#ffc107">
                                <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                            </svg>
                            <span>4.9</span>
                        </div>
                        <div class="doctor-card-footer">
                            <span class="price">$120</span>
                            <button class="book-btn">Book Now</button>
                        </div>
                    </div>
                </div>
                <div class="doctor-card">
                    <div class="doctor-img">
                        <img src="/api/placeholder/200/200" alt="Doctor avatar">
                    </div>
                    <div class="doctor-info">
                        <h3 class="doctor-name">Dr. Michael Chen</h3>
                        <p class="doctor-specialty">Neurologist</p>
                        <div class="rating">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#ffc107">
                                <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                            </svg>
                            <span>4.7</span>
                        </div>
                        <div class="doctor-card-footer">
                            <span class="price">$150</span>
                            <button class="book-btn">Book Now</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="upcoming-appointments">
            <div class="section-header">
                <h2 class="section-title">Upcoming Appointments</h2>
                <a href="#" class="see-all">See All</a>
            </div>
            <div class="appointment-card">
                <div class="appointment-header">
                    <div class="appointment-doctor">
                        <div class="appointment-doctor-img">
                            <img src="/api/placeholder/100/100" alt="Doctor avatar">
                        </div>
                        <div>
                            <h3 class="doctor-name">Dr. Sarah Johnson</h3>
                            <p class="doctor-specialty">Cardiologist</p>
                        </div>
                    </div>
                    <div class="appointment-type">
                        Video Consultation
                    </div>
                </div>
                <div class="appointment-time">
                    <div class="time-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#777">
                            <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-.5-13H13v5.25l4.5 2.67-.75 1.23L11 13V7z"/>
                        </svg>
                        <div class="time-info">
                            <span>Date</span><br>
                            May 22, 2025
                        </div>
                    </div>
                    <div class="time-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#777">
                            <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                        </svg>
                        <div class="time-info">
                            <span>Time</span><br>
                            10:30 AM
                        </div>
                    </div>
                </div>
                <div class="appointment-buttons">
                    <div class="appointment-btn cancel-btn">Cancel</div>
                    <div class="appointment-btn reschedule-btn">Reschedule</div>
                </div>
            </div>
        </section>

        <nav>
            <a href="#" class="nav-item active">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                </svg>
                <span class="nav-text">Home</span>
            </a>
            <a href="#" class="nav-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                </svg>
                <span class="nav-text">Appointments</span>
            </a>
            <a href="#" class="nav-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/>
                </svg>
                <span class="nav-text">Messages</span>
            </a>
            <a href="#" class="nav-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
                <span class="nav-text">Profile</span>
            </a>
        </nav>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const splashScreen = document.getElementById('splash-screen');
            const appContainer = document.getElementById('app-container');
            const getStartedBtn = document.getElementById('get-started-btn');
            
            // Fake loading to simulate PHP backend
            getStartedBtn.addEventListener('click', function() {
                getStartedBtn.textContent = 'Loading...';
                setTimeout(function() {
                    splashScreen.style.display = 'none';
                    appContainer.style.display = 'block';
                    // Simulate fetching data from a PHP backend
                    fetchDoctors();
                }, 1000);
            });
            
            // Simulate PHP backend API call for doctors
            function fetchDoctors() {
                // This would normally fetch data from a PHP backend
                console.log('Fetching doctors from API...');
                // For a real app, you would use fetch() or XMLHttpRequest to call PHP endpoints
            }
            
            // Category selection
            const categories = document.querySelectorAll('.category');
            categories.forEach(category => {
                category.addEventListener('click', function() {
                    categories.forEach(c => c.classList.remove('active'));
                    this.classList.add('active');
                    
                    // This would normally trigger a PHP API call to filter doctors
                    console.log('Selected category:', this.textContent);
                    // Simulate loading data
                    simulateLoading();
                });
            });
            
            function simulateLoading() {
                const doctorsList = document.querySelector('.doctors-list');
                doctorsList.innerHTML = '<div style="text-align: center; width: 100%; padding: 20px;">Loading doctors...</div>';
                
                // Simulate AJAX request to PHP backend
                setTimeout(function() {
                    // Restore original content
                    doctorsList.innerHTML = `
                        <div class="doctor-card">
                            <div class="doctor-img">
                                <img src="/api/placeholder/200/200" alt="Doctor avatar">
                            </div>
                            <div class="doctor-info">
                                <h3 class="doctor-name">Dr. Sarah Johnson</h3>
                                <p class="doctor-specialty">Cardiologist</p>
                                <div class="rating