<?php
// Include database configuration
include("../db_config.php");

// Set content type to JSON
header('Content-Type: application/json');

if (isset($_GET['treatment_id']) && !empty($_GET['treatment_id'])) {
    $treatment_id = mysqli_real_escape_string($conn, $_GET['treatment_id']);

    $query = "SELECT disease_id FROM treatment_disease WHERE treatment_id = '$treatment_id'";
    $result = mysqli_query($conn, $query);

    $diseases = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $diseases[] = $row['disease_id'];
        }
    }

    echo json_encode($diseases);
} else {
    echo json_encode([]);
}

mysqli_close($conn);
