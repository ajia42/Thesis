<?php
// Include database configuration
include("../db_config.php");

// Function to sanitize input
function sanitize($conn, $data)
{
    return mysqli_real_escape_string($conn, trim($data));
}

// Function to generate next service type ID
function generateServiceTypeID($conn)
{
    $sql = "SELECT MAX(service_type_id) AS max_id FROM service_type";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    if (empty($row['max_id'])) {
        return 'ST001';
    }

    $lastID = $row['max_id'];
    $numPart = intval(substr($lastID, 2));
    $newNumPart = $numPart + 1;

    return 'ST' . str_pad($newNumPart, 3, '0', STR_PAD_LEFT);
}

// Initialize form data and messages
$service_type_id = '';
$service_name = '';
$service_fee = '';
$message = '';
$error = '';
$is_edit = false;

// Handle Save, Update, and Delete actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['save_button'])) {
        $service_type_id = sanitize($conn, $_POST['service_type_id']);
        $service_name = sanitize($conn, $_POST['service_name']);
        $service_fee = sanitize($conn, $_POST['service_fee']);

        if (!empty($service_type_id) && !empty($service_name) && is_numeric($service_fee)) {
            $check_query = "SELECT * FROM service_type WHERE service_type_id = '$service_type_id'";
            $check_result = mysqli_query($conn, $check_query);
            if (mysqli_num_rows($check_result) > 0) {
                $error = "Service Type ID already exists.";
            } else {
                $insert_query = "INSERT INTO service_type (service_type_id, service_name, service_fee) 
  VALUES ('$service_type_id', '$service_name', $service_fee)";
                if (mysqli_query($conn, $insert_query)) {
                    $message = "Service Type added successfully!";
                    $service_type_id = $service_name = $service_fee = ''; // Clear form
                } else {
                    $error = "Error adding service type: " . mysqli_error($conn);
                }
            }
        } else {
            $error = "Please fill in all fields correctly.";
        }
    }

    if (isset($_POST['update_button']) && !empty($_POST['service_type_id'])) {
        $service_type_id = sanitize($conn, $_POST['service_type_id']);
        $service_name = sanitize($conn, $_POST['service_name']);
        $service_fee = sanitize($conn, $_POST['service_fee']);

        if (!empty($service_type_id) && !empty($service_name) && is_numeric($service_fee)) {
            $update_query = "UPDATE service_type 
  SET service_name = '$service_name', 
  service_fee = $service_fee 
  WHERE service_type_id = '$service_type_id'";
            if (mysqli_query($conn, $update_query)) {
                $message = "Service Type updated successfully!";
                $service_type_id = $service_name = $service_fee = ''; // Clear form
                $is_edit = false;
            } else {
                $error = "Error updating service type: " . mysqli_error($conn);
            }
        } else {
            $error = "Please fill in all fields correctly for update.";
        }
    }

    if (isset($_POST['delete_button']) && !empty($_POST['service_type_id'])) {
        $service_type_id_to_delete = sanitize($conn, $_POST['service_type_id']);
        $delete_query = "DELETE FROM service_type WHERE service_type_id = '$service_type_id_to_delete'";
        if (mysqli_query($conn, $delete_query)) {
            $message = "Service Type deleted successfully!";
            $service_type_id = $service_name = $service_fee = ''; // Clear form
            $is_edit = false;
        } else {
            $error = "Error deleting service type: " . mysqli_error($conn);
        }
    }
}

// Fetch service type data based on search or all
$search_query = "";
$search_results = [];
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = sanitize($conn, $_GET['search']);
    $search_query = "SELECT * FROM service_type 
  WHERE service_type_id LIKE '%$search_term%' 
  OR service_name LIKE '%$search_term%'";
    $search_result = mysqli_query($conn, $search_query);

    if ($search_result) {
        while ($row = mysqli_fetch_assoc($search_result)) {
            $search_results[] = $row;
        }
    }
} else {
    $all_services_query = "SELECT * FROM service_type";
    $all_services_result = mysqli_query($conn, $all_services_query);

    while ($row = mysqli_fetch_assoc($all_services_result)) {
        $search_results[] = $row;
    }
}

// Handle Edit action from the table
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $edit_id = sanitize($conn, $_GET['edit']);
    $edit_query = "SELECT * FROM service_type WHERE service_type_id = '$edit_id'";
    $edit_result = mysqli_query($conn, $edit_query);
    if (mysqli_num_rows($edit_result) == 1) {
        $row = mysqli_fetch_assoc($edit_result);
        $service_type_id = $row['service_type_id'];
        $service_name = $row['service_name'];
        $service_fee = $row['service_fee'];
        $is_edit = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Type Management</title>
    <link rel="stylesheet" href="patient_management.css">
    <link rel="stylesheet" href="service_type_management.css">

</head>

<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
                Vision Care
            </div>
            <ul class="menu">
                <li><a href="#">Dashboard</a></li>
                <li><a href="#">Patients</a></li>
                <li><a href="#">Staff</a></li>
                <li><a href="#">Appointments</a></li>
                <li class="active"><a href="service_type.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                        Services</a></li>
                <li><a href="#">Diseases</a></li>
                <li><a href="#">General Checkups</a></li>
                <li><a href="#">Treatments</a></li>
                <li><a href="#">Receipts</a></li>
                <li><a href="#">Reports</a></li>
                <li><a href="#">Settings</a></li>
            </ul>
        </aside>
        <main class="main-content">
            <div class="header">
                <h1>Service Type Management</h1>
            </div>

            <?php if ($message): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="service-form">
                    <div class="form-group">
                        <label for="service_type_id">Service Type ID</label>
                        <input type="text" id="service_type_id" name="service_type_id" value="<?php echo htmlspecialchars($service_type_id); ?>" <?php echo $is_edit ? 'readonly' : ''; ?>>
                    </div>
                    <div class="form-group">
                        <label for="service_name">Service Name</label>
                        <input type="text" id="service_name" name="service_name" value="<?php echo htmlspecialchars($service_name); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="service_fee">Service Fee</label>
                        <input type="number" id="service_fee" name="service_fee" value="<?php echo htmlspecialchars($service_fee); ?>" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="save-button" name="save_button" <?php echo $is_edit ? 'disabled' : ''; ?>>Save</button>
                        <button type="submit" class="update-button" name="update_button" <?php echo !$is_edit ? 'disabled' : ''; ?>>Update</button>
                        <button type="submit" class="delete-button" name="delete_button" <?php echo !$is_edit ? 'disabled' : ''; ?>>Delete</button>
                    </div>
                </div>
            </form>

            <div class="patient-list-header">
                <form method="GET" action="">
                    <input type="search" name="search" placeholder="Search service types by ID or name..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit">Search</button>
                </form>
            </div>

            <table class="service-table">
                <thead>
                    <tr>
                        <th>SERVICE TYPE ID</th>
                        <th>SERVICE NAME</th>
                        <th>SERVICE FEE</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($search_results)): ?>
                        <?php foreach ($search_results as $service): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($service['service_type_id']); ?></td>
                                <td><?php echo htmlspecialchars($service['service_name']); ?></td>
                                <td><?php echo htmlspecialchars($service['service_fee']); ?></td>
                                <td><a href="service_type.php?edit=<?php echo htmlspecialchars($service['service_type_id']); ?>">Edit</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">No service types found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
</body>

</html>