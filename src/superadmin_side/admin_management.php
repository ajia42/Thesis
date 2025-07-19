<?php
session_start();
if (!isset($_SESSION['superadmin_id']) || $_SESSION['admin_role'] !== 'superadmin') {
    header('Location: login.php');
    exit();
}

// Include database configuration
include("../db_config.php");

// Function to generate next admin ID
function generateAdminID($conn)
{
    $sql = "SELECT MAX(admin_id) AS max_id FROM admin";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    // If no admin exists, start with A0001
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

// Validation checks
$errors = '';
$message = '';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $user_name = preg_match('/^[A-Za-z\s]+$/', $_POST['user_name']) ? mysqli_real_escape_string($conn, $_POST['user_name']) : '';
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ? mysqli_real_escape_string($conn, $_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : ''; // Modified this line
    $role = 'admin'; // Always set to admin for this management page

    // Action based on button click
    // Action based on button click
    if (isset($_POST['save_button'])) {
        // Add validation checks
        if (empty($user_name)) {
            $errors = "Username must contain only English letters";
        }
        if (empty($email)) {
            $errors = "Invalid email format";
        }
        // In the save_button section:
        if (empty($password)) {
            $errors = "Password is required";
        } elseif (strlen($password) < 8) {
            $errors = "Password must be at least 8 characters";
        } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9!@#$%^&*]/', $password)) {
            $errors = "Password must contain both letters and numbers/symbols";
        }

        // Check if email exists in admin table
        $email_check = "SELECT * FROM admin WHERE email = '$email'";
        $email_result = mysqli_query($conn, $email_check);
        if (mysqli_num_rows($email_result) > 0) {
            $errors = "Email already exists in admin records.";
        }

        if (empty($errors)) {
            // Generate new admin ID
            $admin_id = generateAdminID($conn);

            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Prepare INSERT query for admin
            $insert_admin = "INSERT INTO admin (admin_id, user_name, email, role, password) 
                           VALUES ('$admin_id', '$user_name', '$email', '$role', '$hashed_password')";

            if (mysqli_query($conn, $insert_admin)) {
                $message = "Admin added successfully!";
                $admin_id = $user_name = $email = $password = '';
            } else {
                $errors = "Error adding admin: " . mysqli_error($conn);
            }
        }
    }

    // Handle update functionality
    if (isset($_POST['update_button']) && !empty($_POST['admin_id'])) {
        $admin_id = mysqli_real_escape_string($conn, $_POST['admin_id']);
        $user_name = preg_match('/^[A-Za-z\s]+$/', $_POST['user_name']) ? mysqli_real_escape_string($conn, $_POST['user_name']) : '';
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ? mysqli_real_escape_string($conn, $_POST['email']) : '';
        $original_email = mysqli_real_escape_string($conn, $_POST['original_email']);
        $new_password = isset($_POST['password']) ? $_POST['password'] : ''; // Modified this line

        if (empty($user_name)) {
            $errors = "Username must contain only English letters";
        }
        if (empty($email)) {
            $errors = "Invalid email format";
        }

        if (empty($errors)) {
            // Start transaction
            mysqli_begin_transaction($conn);

            try {
                // Check if email is being changed
                if ($email != $original_email) {
                    // Check if new email exists in admin table (excluding current record)
                    $admin_check = "SELECT * FROM admin WHERE email = '$email' AND email != '$original_email'";
                    $admin_result = mysqli_query($conn, $admin_check);

                    if (mysqli_num_rows($admin_result) > 0) {
                        throw new Exception("Email already registered in admin records");
                    }
                }

                // Prepare update query
                $update_fields = "user_name = '$user_name', email = '$email'";

                // Only update password if a new one was provided
                // In the update_button section:
                if (!empty($new_password)) {
                    if (strlen($new_password) < 8) {
                        throw new Exception("Password must be at least 8 characters");
                    } elseif (!preg_match('/[A-Za-z]/', $new_password) || !preg_match('/[0-9!@#$%^&*]/', $new_password)) {
                        throw new Exception("Password must contain both letters and numbers/symbols");
                    }
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_fields .= ", password = '$hashed_password'";
                }

                // Update admin table
                $update_admin = "UPDATE admin SET $update_fields WHERE admin_id = '$admin_id'";

                if (!mysqli_query($conn, $update_admin)) {
                    throw new Exception("Error updating admin: " . mysqli_error($conn));
                }

                mysqli_commit($conn);
                $message = "Admin updated successfully!";
                $admin_id = $user_name = $email = $password = '';
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $errors = $e->getMessage();
            }
        }
    }

    // Delete functionality
    if (isset($_POST['delete_button']) && !empty($_POST['admin_id'])) {
        $admin_id = mysqli_real_escape_string($conn, $_POST['admin_id']);
        $email = isset($_POST['email']) ? mysqli_real_escape_string($conn, $_POST['email']) : '';

        // Prevent superadmin from deleting themselves
        if ($admin_id === $_SESSION['superadmin_id']) {
            $errors = "Cannot delete your own superadmin account";
        } else {
            $delete_admin = "DELETE FROM admin WHERE admin_id = '$admin_id'";
            if (mysqli_query($conn, $delete_admin)) {
                $message = "Admin deleted successfully!";
            } else {
                $errors = "Error deleting admin: " . mysqli_error($conn);
            }
        }
    }
}

// Search functionality
$search_query = "";
$search_results = [];
$no_results_message = "";
$is_search = false;

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $is_search = true;
    $search_term = mysqli_real_escape_string($conn, $_GET['search']);
    $search_query = "SELECT * FROM admin 
                 WHERE (admin_id LIKE '%$search_term%' 
                 OR user_name LIKE '%$search_term%' 
                 OR email LIKE '%$search_term%')
                 AND admin_id != '" . $_SESSION['superadmin_id'] . "'";
    $search_result = mysqli_query($conn, $search_query);

    if ($search_result) {
        while ($row = mysqli_fetch_assoc($search_result)) {
            // Don't include password hash in results
            unset($row['password']);
            $search_results[] = $row;
        }

        if (empty($search_results)) {
            $no_results_message = "No results found for: '" . htmlspecialchars($_GET['search']) . "'";
        }
    }
}

// Fetch all admins only if no search is performed
if (!$is_search && empty($search_results)) {
    $all_admins_query = "SELECT admin_id, user_name, email, role FROM admin 
                     WHERE admin_id != '" . $_SESSION['superadmin_id'] . "'";
    $all_admins_result = mysqli_query($conn, $all_admins_query);

    while ($row = mysqli_fetch_assoc($all_admins_result)) {
        $search_results[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management</title>
    <link rel="stylesheet" href="admin_management.css">
    <link rel="icon" href="../images/logo.svg" type="image/svg+xml">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Phetsarath:wght@400;700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo">
                <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                    <path d="M12 4a4 4 0 100 8 4 4 0 000-8zM2 12C2 6.486 6.486 2 12 2s10 4.486 10 10-4.486 10-10 10S2 17.514 2 12z"></path>
                </svg>
                Vision Care
            </div>

            <ul class="menu">
                <li>
                    <a href="admin_profile.php">
                        <div class="staff-info">
                            <svg xmlns="http://www.w3.org/2000/svg" style="color: #2c3e50;" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-user-round-icon lucide-circle-user-round">
                                <path d="M18 20a6 6 0 0 0-12 0" />
                                <circle cx="12" cy="10" r="4" />
                                <circle cx="12" cy="12" r="10" />
                            </svg>
                            <p><?php echo htmlspecialchars($_SESSION["superadmin_user_name"]); ?></p>
                        </div>
                    </a>
                </li>

                <li><a href="#">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="3" y1="9" x2="21" y2="9"></line>
                            <line x1="9" y1="21" x2="9" y2="9"></line>
                        </svg>
                        Dashboard</a></li>

                <li class="active"><a href="admin_management.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        ຈັດການແອັດມິນ</a></li>

                <li><a href="logout.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-out-icon lucide-log-out">
                            <path d="m16 17 5-5-5-5" />
                            <path d="M21 12H9" />
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                        </svg>
                        ອອກຈາກລະບົບ</a></li>
            </ul>
        </aside>
        <main class="main-content">
            <div class="header">
                <h1>Admin Management</h1>
                <button class="new-patient-button" name="new_admin" onclick="clearForm()">+ Add New Admin</button>
            </div>

            <?php if ($message): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($errors): ?>
                <div class="error"><?php echo $errors; ?></div>
            <?php endif; ?>

            <!-- Admin Form -->
            <form method="POST" action="" id="adminForm">
                <input type="hidden" id="original_email" name="original_email">
                <div class="patient-form">
                    <div class="form-group">
                        <label for="adminID">Admin ID</label>
                        <input type="text" id="adminID" name="admin_id" readonly>
                    </div>
                    <div class="form-group">
                        <label for="userName">Username</label>
                        <input
                            type="text"
                            id="userName"
                            name="user_name"
                            pattern="[A-Za-z ]+"
                            title="Only English letters are allowed"
                            required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            minlength="8"
                            pattern="^(?=.*[A-Za-z])(?=.*[\d!@#$%^&*]).{8,}$"
                            placeholder="Leave blank to keep current password when updating" required>
                        <div id="passwordError" class="error-message" style="display: none;"></div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="save-button" name="save_button" id="saveButton">Save</button>
                        <button type="submit" class="update-button" name="update_button" id="updateButton">Update</button>
                        <button type="submit" class="delete-button" name="delete_button" id="deleteButton">Delete</button>
                    </div>
                </div>
            </form>

            <!-- Search Form -->
            <div class="patient-list-header">
                <form method="GET" action="">
                    <input type="search" name="search" placeholder="Search admins by name or email..."
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit">Search</button>
                </form>
            </div>

            <?php if (!empty($no_results_message)): ?>
                <div class="alert alert-info">
                    <?php echo $no_results_message; ?>
                </div>
            <?php endif; ?>

            <!-- Admin Table -->
            <table class="patient-table">
                <?php if (!empty($search_results)): ?>
                    <thead>
                        <tr>
                            <th>Admin ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Actions</th>
                            <th>Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($search_results as $admin): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($admin['admin_id']); ?></td>
                                <td><?php echo htmlspecialchars($admin['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                <td><?php echo htmlspecialchars($admin['role']); ?></td>
                                <td>
                                    <a href="#" onclick="showEditModal(
        '<?php echo htmlspecialchars($admin['admin_id']); ?>', 
        '<?php echo htmlspecialchars($admin['user_name']); ?>', 
        '<?php echo htmlspecialchars($admin['email']); ?>'
    )">Edit</a>
                                </td>
                                <td>
                                    <a href="#" class="delete-link" onclick="confirmDelete('<?php echo htmlspecialchars($admin['admin_id']); ?>', '<?php echo htmlspecialchars($admin['email']); ?>')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
            </table>
        <?php endif; ?>
        </main>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h2>Edit Admin Details</h2>
            <form id="editForm" method="POST" action="admin_management.php">
                <input type="hidden" id="modal_admin_id" name="admin_id">
                <input type="hidden" id="modal_original_email" name="original_email">

                <div class="patient-form">
                    <!-- Admin ID (readonly) -->
                    <div class="form-group">
                        <label for="display_admin_id">Admin ID</label>
                        <input type="text" id="display_admin_id" readonly class="readonly-field">
                    </div>

                    <!-- Username (editable) -->
                    <div class="form-group">
                        <label for="modal_user_name">Username</label>
                        <input type="text" id="modal_user_name" name="user_name" pattern="[A-Za-z ]+"
                            title="Only English letters are allowed" required>
                    </div>

                    <!-- Email (editable) -->
                    <div class="form-group">
                        <label for="modal_email">Email</label>
                        <input type="email" id="modal_email" name="email" required>
                    </div>

                    <!-- Current Password (for verification) -->
                    <div class="form-group">
                        <label for="current_password">Current Password (for verification)</label>
                        <input type="password" id="current_password" name="current_password">
                        <div id="current_password_error" class="error-message" style="display: none;"></div>
                    </div>

                    <!-- New Password -->
                    <div class="form-group">
                        <label for="modal_password">New Password (leave blank to keep current)</label>
                        <input type="password" id="modal_password" name="password"
                            minlength="8" pattern="^(?=.*[A-Za-z])(?=.*[\d!@#$%^&*]).{8,}$"
                            title="Must contain at least one letter and one number/symbol, and be at least 8 characters long">
                        <div id="password_error" class="error-message" style="display: none;"></div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="cancel-button" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="update-button" name="update_button">Update</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content" style="width: 40%;">
            <h2>Confirm Delete</h2>
            <p>Are you sure you want to delete this admin?</p>
            <form id="deleteForm" method="POST" action="">
                <input type="hidden" id="delete_admin_id" name="admin_id">
                <input type="hidden" id="delete_email" name="email">
                <input type="hidden" name="user_name" value="">
                <input type="hidden" name="password" value="">
                <div class="modal-actions">
                    <button type="button" class="cancel-button" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="delete-button" name="delete_button">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function fillForm(adminId, userName, email) {
            document.getElementById('adminID').value = adminId;
            document.getElementById('userName').value = userName;
            document.getElementById('email').value = email;
            document.getElementById('original_email').value = email;
            document.getElementById('password').value = '';

            // Disable save button, enable update and delete
            document.getElementById('saveButton').disabled = true;
            document.getElementById('updateButton').disabled = false;
            document.getElementById('deleteButton').disabled = false;

            // Scroll to form
            document.getElementById('adminForm').scrollIntoView({
                behavior: 'smooth'
            });
        }

        function clearForm() {
            document.getElementById('adminID').value = '';
            document.getElementById('userName').value = '';
            document.getElementById('email').value = '';
            document.getElementById('original_email').value = '';
            document.getElementById('password').value = '';
            document.getElementById('userName').focus();

            // Enable save button, disable update and delete
            document.getElementById('saveButton').disabled = false;
            document.getElementById('updateButton').disabled = true;
            document.getElementById('deleteButton').disabled = true;
        }

        // Add this to initialize the form state when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Initially disable update and delete buttons
            document.getElementById('updateButton').disabled = true;
            document.getElementById('deleteButton').disabled = true;

            // If there's an admin ID in the form (from form submission error), 
            // we should disable save and enable update/delete
            if (document.getElementById('adminID').value) {
                document.getElementById('saveButton').disabled = true;
                document.getElementById('updateButton').disabled = false;
                document.getElementById('deleteButton').disabled = false;
            }
        });

        // Add this to prevent form submission with wrong button states
        document.getElementById('adminForm').addEventListener('submit', function(e) {
            const adminId = document.getElementById('adminID').value;
            const isSave = e.submitter.name === 'save_button';
            const isUpdate = e.submitter.name === 'update_button';
            const isDelete = e.submitter.name === 'delete_button';

            if (isSave && adminId) {
                e.preventDefault();
                alert("Error: You're trying to save an existing record. Use Update instead.");
                return;
            }

            if ((isUpdate || isDelete) && !adminId) {
                e.preventDefault();
                alert("Error: No admin selected. Please select an admin to edit first.");
                return;
            }
        });

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Validate username input (English letters only)
        const userNameInput = document.getElementById('userName');

        userNameInput.addEventListener('keypress', function(e) {
            if (!/^[A-Za-z ]$/.test(e.key)) {
                e.preventDefault();
            }
        });

        userNameInput.addEventListener('paste', function(e) {
            e.preventDefault();
            const pasteText = (e.clipboardData || window.clipboardData).getData('text');
            const filteredText = pasteText.replace(/[^A-Za-z ]/g, '');
            document.execCommand('insertText', false, filteredText);
        });

        userNameInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^A-Za-z ]/g, '');
        });

        // Add this to your existing JavaScript in admin_management.php
        document.getElementById('password').addEventListener('input', function() {
            validatePassword();
        });

        function validatePassword() {
            const password = document.getElementById('password').value;
            const passwordError = document.getElementById('passwordError');

            if (!password) {
                passwordError.style.display = 'none';
                return true; // Skip validation if empty (for update case)
            }

            if (password.length < 8) {
                showPasswordError("Password must be at least 8 characters");
                return false;
            }

            if (!/[A-Za-z]/.test(password) || !/[0-9!@#$%^&*]/.test(password)) {
                showPasswordError("Password must contain both letters and numbers/symbols");
                return false;
            }

            // If validation passes
            passwordError.style.display = 'none';
            return true;
        }

        function showPasswordError(message) {
            const passwordError = document.getElementById('passwordError');
            passwordError.textContent = message;
            passwordError.style.display = 'block';
        }

        // Update the form submission validation
        document.getElementById('adminForm').addEventListener('submit', function(e) {
            const isSave = e.submitter.name === 'save_button';
            const password = document.getElementById('password').value;

            if (isSave && !validatePassword()) {
                e.preventDefault();
                return;
            }

            // Rest of your existing validation...
        });

        // Show edit modal with admin details
        function showEditModal(adminId, userName, email) {
            document.getElementById('modal_admin_id').value = adminId;
            document.getElementById('display_admin_id').value = adminId;
            document.getElementById('modal_user_name').value = userName;
            document.getElementById('modal_email').value = email;
            document.getElementById('modal_original_email').value = email;
            document.getElementById('current_password').value = '';
            document.getElementById('modal_password').value = '';

            document.getElementById('editModal').style.display = 'block';
        }

        // Close modal
        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Confirm delete
        function confirmDelete(adminId, email) {
            document.getElementById('delete_admin_id').value = adminId;
            document.getElementById('delete_email').value = email;
            // Set empty values for other required fields
            document.querySelector('#deleteForm input[name="user_name"]').value = '';
            document.querySelector('#deleteForm input[name="password"]').value = '';
            document.getElementById('deleteModal').style.display = 'block';
        }

        // Close delete modal
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('editModal')) {
                closeModal();
            }
            if (event.target == document.getElementById('deleteModal')) {
                closeDeleteModal();
            }
        }

        // Update the edit link in the table to use the modal
        function fillForm(adminId, userName, email) {
            showEditModal(adminId, userName, email);
        }

        // Password verification before form submission
        document.getElementById('editForm').addEventListener('submit', function(e) {
            const adminId = document.getElementById('modal_admin_id').value;
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('modal_password').value;

            // Clear previous errors
            document.getElementById('current_password_error').style.display = 'none';
            document.getElementById('password_error').style.display = 'none';

            // Only validate passwords if a new password is provided
            if (newPassword.trim() !== '') {
                // First verify current password
                if (currentPassword.trim() === '') {
                    e.preventDefault();
                    document.getElementById('current_password_error').textContent = "Current password is required to change password";
                    document.getElementById('current_password_error').style.display = 'block';
                    return;
                }

                // Verify password strength if new password is provided
                if (newPassword.length < 8) {
                    e.preventDefault();
                    document.getElementById('password_error').textContent = "Password must be at least 8 characters";
                    document.getElementById('password_error').style.display = 'block';
                    return;
                }

                if (!/[A-Za-z]/.test(newPassword) || !/[0-9!@#$%^&*]/.test(newPassword)) {
                    e.preventDefault();
                    document.getElementById('password_error').textContent = "Password must contain both letters and numbers/symbols";
                    document.getElementById('password_error').style.display = 'block';
                    return;
                }

                // Verify current password via AJAX
                e.preventDefault();
                verifyCurrentPassword(adminId, currentPassword, newPassword);
            }
            // If no password change, form will submit normally
        });

        // AJAX function to verify current password
        function verifyCurrentPassword(adminId, currentPassword, newPassword) {
            fetch('verify_admin_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `admin_id=${encodeURIComponent(adminId)}&current_password=${encodeURIComponent(currentPassword)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.valid) {
                        // Password is correct, submit the form
                        document.getElementById('editForm').removeEventListener('submit', arguments.callee);
                        document.getElementById('editForm').submit();
                    } else {
                        // Show error
                        document.getElementById('current_password_error').textContent = "Current password is incorrect";
                        document.getElementById('current_password_error').style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('current_password_error').textContent = "Error verifying password";
                    document.getElementById('current_password_error').style.display = 'block';
                });
        }
    </script>
</body>

</html>