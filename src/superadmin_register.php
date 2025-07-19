<?php
include("db_config.php");

// Set the new admin's info
$username = "superadmin";
$plain_password = "paneeda-2025"; // Set your new password
$role = "superadmin";
$gmail = "adminex1@example.com";
$gender = "female";
$tel = "050505055050505";

// Hash the password securely
$hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

// Prepare the INSERT SQL
$sql = "INSERT INTO wadmin (ad_username, admin_password, ad_role, gmail, gender, tel)
        VALUES ('$username', '$hashed_password', '$role', '$gmail', '$gender', '$tel')";

if (mysqli_query($conn, $sql)) {
    echo "New admin inserted successfully.";
} else {
    echo "Error: " . mysqli_error($conn);
}

$conn->close();
