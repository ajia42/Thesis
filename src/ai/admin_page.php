<?php
include("../db_config.php");

if (isset($_POST['save_button'])) {
 $first_name = $_POST['first_name'];
 $last_name = $_POST['last_name'];
 $gender = $_POST['gender'];
 $email = $_POST['email'];
 $phone = $_POST['phone'];
 $dob = $_POST['dob'];
 $address = $_POST['address'];

 // Check if email or phone already exists
 $check_query = "SELECT * FROM patient WHERE email='$email' OR phone='$phone'";
 $check_result = $conn->query($check_query);

 if ($check_result->num_rows > 0) {
 echo "<script>alert('Email or phone number already exists!');</script>";
 } else {
 // Generate patient_id
 $id_query = "SELECT MAX(SUBSTRING(patient_id, 2)) AS max_id FROM patient";
 $id_result = $conn->query($id_query);
 $id_row = $id_result->fetch_assoc();
 $max_id = $id_row['max_id'];
 $new_id = 'P' . str_pad($max_id + 1, 4, '0', STR_PAD_LEFT);

 $sql = "INSERT INTO patient (patient_id, first_name, last_name, gender, email, phone, dob, address)
 VALUES ('$new_id', '$first_name', '$last_name', '$gender', '$email', '$phone', '$dob', '$address')";

 if ($conn->query($sql) === TRUE) {
 echo "<script>alert('New patient added successfully');</script>";
 } else {
 echo "Error: " . $sql . "<br>" . $conn->error;
 }
 }
}
?> 

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Management</title>
    <style>
        body {
    font-family: sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f4f6f8;
    display: flex;
}

.container {
    display: flex;
    width: 100%;
}

.sidebar {
    background-color: #3f51b5;/* Example theme color */
    color: white;
    width: 200px;
    height: 100%;
    padding: 20px;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}
 
.sidebar .logo {
    font-size: 1.5em;
    font-weight: bold;
    margin-bottom: 20px;
}

.sidebar .menu {
    list-style: none;
    padding: 0;
    margin: 0;
    width: 100%;
}

.sidebar .menu li {
    margin-bottom: 10px;
    
}

.sidebar .menu li a {
    display: block;
    color: white;
    text-decoration: none;
    padding: 10px 15px;
    border-radius: 5px;
}

.sidebar .menu li.active a,
.sidebar .menu li a:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.main-content {
    flex-grow: 1;
    padding: 20px;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.header h1 {
    margin: 0;
}

.new-patient-button {
    background-color: #388e3c; /* Example button color */
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 5px;
    cursor: pointer;
}

.patient-form {
    background-color: white;
    padding: 20px;
    border-radius: 5px;
    margin-bottom: 20px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.patient-form .form-group {
    display: flex;
    flex-direction: column;
}

.patient-form label {
    margin-bottom: 5px;
    font-weight: bold;
    color: #555;
}

.patient-form input[type="text"],
.patient-form input[type="email"],
.patient-form input[type="tel"],
.patient-form input[type="date"],
.patient-form select {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 3px;
}

.patient-form .address {
    grid-column: 1 / -1; /* Make address span full width */
}

.patient-form .form-actions {
    grid-column: 1 / -1;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.patient-form .form-actions button {
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 5px;
    cursor: pointer;
}

.patient-form .save-button {
    background-color: #1976d2; /* Example save button color */
}

.patient-form .update-button {
    background-color: #ffa000; /* Example update button color */
}

.patient-form .delete-button {
    background-color: #d32f2f; /* Example delete button color */
}

.patient-list-header {
    margin-bottom: 10px;
}

.patient-list-header input[type="search"] {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 3px;
    width: 300px;
}

.patient-table {
    width: 100%;
    border-collapse: collapse;
    background-color: white;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.patient-table th,
.patient-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.patient-table th {
    background-color: #f9f9f9;
    font-weight: bold;
}

.patient-table tbody tr:last-child td {
    border-bottom: none;
}

.patient-table tbody tr:hover {
    background-color: #f5f5f5;
}

.patient-table .actions a {
    color: #1976d2;
    text-decoration: none;
}

.patient-table .actions a:hover {
    text-decoration: underline;
}
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            </svg>    
            Vision Care</div>
            <ul class="menu">

                <li><a href="#">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="3" y1="9" x2="21" y2="9"></line>
                    <line x1="9" y1="21" x2="9" y2="9"></line>
                </svg>    
                Dashboard</a></li>

                <li><a href="#">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                Patients</a></li>

                <li><a href="#">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>    
                Staff</a></li>

                <li><a href="#">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>    
                Appointments</a></li>

                <li><a href="#">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>    
                Services</a></li>

                <li><a href="#">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>    
                Diseases</a></li>

                <li><a href="#">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20.42 4.58a5.4 5.4 0 0 0-7.65 0l-.77.78-.77-.78a5.4 5.4 0 0 0-7.65 0C1.46 6.7 1.33 10.28 4 13l8 8 8-8c2.67-2.72 2.54-6.3.42-8.42z"></path>
                </svg>    
                General Checkups</a></li>

                <li><a href="#">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
                    Treatments</a></li>

                <li><a href="#">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>    
                Receipts</a></li>

                <li><a href="#">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>    
                Reports</a></li>

                <li><a href="#">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>    
                    Settings</a>
                </li>
            </ul>
        </aside>
        <main class="main-content">
            <div class="header">
                <h1>Patient Management</h1>
                <button class="new-patient-button" name="new_patient">+ New Patient</button>
            </div>
            <div class="patient-form">
                <div class="form-group">
                    <label for="patientID">Patient ID</label>
                    <input type="text" id="patientID" name="patient_id">
                </div>
                <div class="form-group">
                    <label for="firstName">First Name</label>
                    <input type="text" id="firstName" name="first_name">
                </div>
                <div class="form-group">
                    <label for="lastName">Last Name</label>
                    <input type="text" id="lastName" name="last_name">
                </div>
                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender">
                        <option value="Male" selected>Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email">
                </div>
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone">
                </div>
                <div class="form-group">
                    <label for="dob">Date of Birth</label>
                    <input type="date" id="dob" name="dob" placeholder="dd/mm/yyyy">
                </div>
                <div class="form-group address">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address">
                </div>
                <div class="form-actions">
                    <button class="save-button" name="save_button">Save</button>
                    <button class="update-button" name="update_button">Update</button>
                    <button class="delete-button" name="delete_button">Delete</button>
                </div>
            </div>

            <div class="patient-list-header">
                <input type="search" placeholder="Search patients by name or email...">
            </div>
        
            <table class="patient-table">
                <thead>
                    <tr>
                        <th>PATIENT ID</th>
                        <th>FIRST NAME</th>
                        <th>LAST NAME</th>
                        <th>GENDER</th>
                        <th>EMAIL</th>
                        <th>PHONE</th>
                        <th>DATE OF BIRTH</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>P001</td>
                        <td>John</td>
                        <td>Doe</td>
                        <td>Male</td>
                        <td>john@example.com</td>
                        <td>(555) 123-4567</td>
                        <td>6/15/1985</td>
                        <td><a href="#">Edit</a></td>
                    </tr>
                    <tr>
                        <td>P002</td>
                        <td>Jane</td>
                        <td>Smith</td>
                        <td>Female</td>
                        <td>jane@example.com</td>
                        <td>(555) 234-5678</td>
                        <td>3/22/1990</td>
                        <td><a href="#">Edit</a></td>
                    </tr>
                    </tbody>
            </table>
        </main>
    </div>
</body>
</html>