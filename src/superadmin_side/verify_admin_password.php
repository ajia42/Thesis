<?php
session_start();
include("../db_config.php");

header('Content-Type: application/json');

if (!isset($_SESSION['superadmin_id']) || $_SESSION['admin_role'] !== 'superadmin') {
    echo json_encode(['valid' => false, 'error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_id = mysqli_real_escape_string($conn, $_POST['admin_id']);
    $current_password = $_POST['current_password'];

    // Get the admin's current password hash
    $sql = "SELECT password FROM admin WHERE admin_id = '$admin_id'";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $stored_hash = $row['password'];

        // Verify the current password
        if (password_verify($current_password, $stored_hash)) {
            echo json_encode(['valid' => true]);
        } else {
            echo json_encode(['valid' => false]);
        }
    } else {
        echo json_encode(['valid' => false]);
    }
} else {
    echo json_encode(['valid' => false, 'error' => 'Invalid request method']);
}
