<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DocTime - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #7DD3FC 0%, #0EA5E9 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .login-container:hover {
            transform: translateY(-5px);
        }

        .logo-container {
            margin-bottom: 30px;
            position: relative;
        }

        .logo-circle {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .medical-cross {
            width: 40px;
            height: 40px;
            border: 3px solid #0EA5E9;
            border-radius: 50%;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .medical-cross::before,
        .medical-cross::after {
            content: '';
            position: absolute;
            background: #0EA5E9;
        }

        .medical-cross::before {
            width: 3px;
            height: 20px;
        }

        .medical-cross::after {
            width: 20px;
            height: 3px;
        }



        .brand-title {
            font-size: 28px;
            font-weight: 600;
            color: #1E293B;
            margin-bottom: 10px;
        }

        .brand-subtitle {
            color: #64748B;
            font-size: 16px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
            font-size: 14px;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
        }

        .form-input:focus {
            outline: none;
            border-color: #0EA5E9;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
        }

        .form-input::placeholder {
            color: #9CA3AF;
        }

        .forgot-password {
            text-align: right;
            margin-bottom: 25px;
        }

        .forgot-password a {
            color: #0EA5E9;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .forgot-password a:hover {
            color: #0284C7;
        }

        .login-btn {
            width: 100%;
            background: linear-gradient(135deg, #0EA5E9 0%, #0284C7 100%);
            color: white;
            border: none;
            padding: 14px 20px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(14, 165, 233, 0.3);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .divider {
            margin: 25px 0;
            position: relative;
            text-align: center;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #E5E7EB;
        }

        .divider span {
            background: rgba(255, 255, 255, 0.95);
            padding: 0 15px;
            color: #6B7280;
            font-size: 14px;
        }

        .signup-link {
            color: #6B7280;
            font-size: 14px;
        }

        .signup-link a {
            color: #0EA5E9;
            text-decoration: none;
            font-weight: 600;
        }

        .signup-link a:hover {
            text-decoration: underline;
        }

        .error-message {
            background: #FEE2E2;
            color: #DC2626;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
        }

        .success-message {
            background: #D1FAE5;
            color: #059669;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
                margin: 10px;
            }

            .brand-title {
                font-size: 24px;
            }

            .logo-circle {
                width: 70px;
                height: 70px;
            }

            .medical-cross {
                width: 35px;
                height: 35px;
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-container {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-container">
            <div class="logo-circle">
                <div class="medical-cross"></div>
            </div>
            <h1 class="brand-title">DocTime</h1>
            <p class="brand-subtitle">Welcome back to your healthcare portal</p>
        </div>

        <div class="error-message" id="errorMessage"></div>
        <div class="success-message" id="successMessage"></div>

        <form id="loginForm" action="#" method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-input" 
                    placeholder="Enter your email address"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-input" 
                    placeholder="Enter your password"
                    required
                >
            </div>

            <div class="forgot-password">
                <a href="#" onclick="showForgotPassword()">Forgot your password?</a>
            </div>

            <button type="submit" class="login-btn">Sign In</button>
        </form>

        <div class="divider">
            <span>or</span>
        </div>

        <div class="signup-link">
            Don't have an account? <a href="#" onclick="showSignup()">Sign up here</a>
        </div>
    </div>

    <script>
        // Form validation and handling
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const errorDiv = document.getElementById('errorMessage');
            const successDiv = document.getElementById('successMessage');
            
            // Hide previous messages
            errorDiv.style.display = 'none';
            successDiv.style.display = 'none';
            
            // Basic validation
            if (!email || !password) {
                showError('Please fill in all fields');
                return;
            }
            
            if (!isValidEmail(email)) {
                showError('Please enter a valid email address');
                return;
            }
            
            if (password.length < 6) {
                showError('Password must be at least 6 characters long');
                return;
            }
            
            // Simulate login process
            const submitBtn = document.querySelector('.login-btn');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Signing In...';
            submitBtn.disabled = true;
            
            // Simulate API call
            setTimeout(() => {
                // Reset button
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
                
                // For demo purposes, show success for specific credentials
                if (email === 'demo@doctime.com' && password === 'demo123') {
                    showSuccess('Login successful! Redirecting to dashboard...');
                    setTimeout(() => {
                        window.location.href = '#dashboard'; // Replace with actual dashboard URL
                    }, 2000);
                } else {
                    showError('Invalid email or password. Try demo@doctime.com / demo123');
                }
            }, 1500);
        });
        
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
        
        function showError(message) {
            const errorDiv = document.getElementById('errorMessage');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            errorDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        
        function showSuccess(message) {
            const successDiv = document.getElementById('successMessage');
            successDiv.textContent = message;
            successDiv.style.display = 'block';
            successDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        
        function showForgotPassword() {
            alert('Forgot password functionality would redirect to password reset page');
            // In a real application, this would redirect to a password reset page
            // window.location.href = 'forgot-password.php';
        }
        
        function showSignup() {
            alert('Sign up functionality would redirect to registration page');
            // In a real application, this would redirect to a signup page
            // window.location.href = 'signup.php';
        }
        
        // Add some interactive effects
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });
        
        // Add floating animation to icons
        function animateIcons() {
            const icons = document.querySelectorAll('.icon');
            icons.forEach((icon, index) => {
                icon.style.animation = `float 3s ease-in-out ${index * 0.5}s infinite`;
            });
        }
        
        // Add CSS animation for floating effect
        const style = document.createElement('style');
        style.textContent = `
            @keyframes float {
                0%, 100% { transform: translateY(0px); }
                50% { transform: translateY(-10px); }
            }
        `;
        document.head.appendChild(style);
        
        // Start animations when page loads
        window.addEventListener('load', animateIcons);
        
        // Add responsive behavior for mobile
        function handleResize() {
            if (window.innerWidth <= 480) {
                document.querySelector('.login-container').style.margin = '10px';
            }
        }
        
        window.addEventListener('resize', handleResize);
        handleResize(); // Call on initial load
    </script>
</body>
</html>