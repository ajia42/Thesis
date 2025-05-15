<?php
session_start(); // Start the session to store user information upon successful login

include("../db_config.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Get user input from the form
  $email = $_POST["email_1"];
  $password = $_POST["password"];

  // Prepare SQL query to fetch user by email
  $sql = "SELECT * FROM staff WHERE email = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows == 1) {
    // User found, now verify the password
    $row = $result->fetch_assoc();
    if ($password == $row["password"]) { // In a real application, use password_verify() with hashed passwords
      // Password is correct, set session variables and redirect
      $_SESSION["staff_id"] = $row["staff_id"];
      header("Location: patient_management.php?id=" . $row["staff_id"]);
      exit();
    } else {
      // Incorrect password
      $_SESSION['login_error'] = "Incorrect password.";
      $_SESSION['login_email'] = $email;
      header("Location: signin_staff.php");
      exit();
    }
  } else {
    // User not found
    $_SESSION['login_error'] = "Incorrect email.";
    $_SESSION['login_email'] = $email;
    header("Location: signin_staff.php");
    exit();
  }

  $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign In - Vision Care</title>
  <link rel="stylesheet" href="signin_staff.css">
</head>

<body>
  <header>
    <div class="container header-content">
      <div class="logo">
        <svg viewBox="0 0 24 24" fill="currentColor" class="icon">
          <path
            d="M12 4a4 4 0 100 8 4 4 0 000-8zM2 12C2 6.486 6.486 2 12 2s10 4.486 10 10-4.486 10-10 10S2 17.514 2 12z"></path>
        </svg>
        <span>Vision Care</span>
      </div>
      <div class="auth-links">
        <a href="#">Login</a>
        <a href="#">Register</a>
      </div>
    </div>
  </header>
  <main class="container sign-in-container">
    <div class="sign-in-card">
      <div class="logo-center">
        <svg viewBox="0 0 24 24" fill="currentColor" class="icon-large">
          <path
            d="M12 4a4 4 0 100 8 4 4 0 000-8zM2 12C2 6.486 6.486 2 12 2s10 4.486 10 10-4.486 10-10 10S2 17.514 2 12z"></path>
        </svg>
        <h2>Vision Care</h2>
      </div>
      <h1>Sign in to your account</h1>
      <p class="create-account">Or <a href="#">create a new account</a></p>
      <?php
      if (isset($_SESSION['login_error'])) {
        echo '<p class="error-message">' . $_SESSION['login_error'] . '</p>';
        unset($_SESSION['login_error']); // Clear the error message after displaying it
      }
      ?>
      <form action="#" method="POST">
        <div class="input-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email_1" required
            value="<?php echo isset($_SESSION['login_email']) ? htmlspecialchars($_SESSION['login_email']) : ''; ?>" />
        </div>
        <div class="input-group">
          <label for="password">Password</label>
          <div class="password-input">
            <input type="password" id="password" name="password" required />
            <button type="button" class="toggle-password" onclick="togglePasswordVisibility()">
              <svg viewBox="0 0 24 24" fill="currentColor" class="eye-icon" id="eye-icon">
                <path
                  d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"></path>
              </svg>
            </button>
          </div>
        </div>
        <div class="form-options">
          <label class="checkbox-label">
            <input type="checkbox" name="remember">
            Remember me
          </label>
          <a href="#" class="forgot-password">Forgot your password?</a>
        </div>
        <button type="submit" class="sign-in-button">
          <svg viewBox="0 0 24 24" fill="currentColor" class="arrow-icon">
            <path d="M10 17l5-5-5-5v10z"></path>
            <path
              d="M19 12c0 4.14-3.36 7.5-7.5 7.5S4 16.14 4 12 7.36 4.5 12 4.5s7.5 3.36 7.5 7.5zM12 6.5c-3.04 0-5.5 2.46-5.5 5.5s2.46 5.5 5.5 5.5 5.5-2.46 5.5-5.5-2.46-5.5-5.5-5.5z"></path>
          </svg>
          Sign in
        </button>
      </form>
    </div>
  </main>

  <script>
    function togglePasswordVisibility() {
      const passwordInput = document.getElementById('password');
      const eyeIcon = document.getElementById('eye-icon');

      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        // Change to "hide password" icon (eye with slash)
        eyeIcon.innerHTML = `
            <path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>
          `;
      } else {
        passwordInput.type = 'password';
        // Change back to "show password" icon (eye)
        eyeIcon.innerHTML = `
            <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
          `;
      }
    }
  </script>
</body>

</html>

<?php
unset($_SESSION['login_email']); // Clear the email from the session after displaying it (optional)
?>