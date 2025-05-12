<?php
// Start session if not already started
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect to index page
    header("Location: index.php");
    exit;
}

// Initialize variables
$email = "";
$password = "";
$error = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    
    // Basic validation
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // Database connection
        $conn = new mysqli("localhost", "username", "password", "toroller"); // Replace with your actual database credentials
        
        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
        // Prepare SQL statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, email, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user["password"])) {
                // Password is correct, start a new session
                session_start();
                
                // Store data in session variables
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["email"] = $user["email"];

                
                // Redirect to index page
                header("Location: index.php");
                exit;
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TorollerCollective</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }
        
        body {
            width: 100%;
            min-height: 100vh;
            background-color: #ffffff;
        }
        
        .header {
            width: 100%;
            height: 118px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-left: 110px;
            padding-right: 110px;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo-text {
            color: #04CD00;
            font-size: 30px;
            font-weight: 800;
            font-family: 'Inter', sans-serif;
        }
        
        .nav-menu {
            display: flex;
            align-items: center;
            gap: 24px;
        }
        
        .nav-links {
            display: flex;
            align-items: center;
            gap: 33px;
        }
        
        .nav-link {
            color: #BDD3C6;
            font-size: 18px;
        }
        
        .nav-link-with-icon {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .auth-buttons {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .login-btn {
            color: #BDD3C6;
            font-size: 16px;
            padding: 18px 24px;
            border: 1px solid #7FE47E;
            border-radius: 30px;
            text-decoration: none;
        }
        
        .get-started-btn {
            color: #ffffff;
            font-size: 16px;
            font-weight: 700;
            padding: 18px 24px;
            background-color: #04CD00;
            border-radius: 30px;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }
        
        .hamburger-menu {
            display: none;
        }
        
        .main-content {
            display: flex;
            justify-content: space-between;
            padding-left: 110px;
            padding-right: 110px;
            padding-top: 100px;
            padding-bottom: 100px;
        }
        
        .left-section {
            display: flex;
            flex-direction: column;
            gap: 216px;
        }
        
        .main-heading {
            color: #04CD00;
            font-size: 56px;
            font-weight: 700;
            line-height: 66px;
        }
        
        .features {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .feature {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        
        .feature-text {
            color: #04CD00;
            font-size: 18px;
            font-weight: 700;
        }
        
        .login-form-container {
            width: 506px;
            background-color: #ffffff;
            border-radius: 30px;
            box-shadow: 0px 4px 4px 0px rgba(0, 0, 0, 0.25);
            padding: 40px;
        }
        
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .form-label {
            color: #333;
            font-size: 20px;
            font-weight: 700;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
        }
        
        .submit-btn {
            width: 100%;
            padding: 16px;
            background-color: #04CD00;
            color: #ffffff;
            font-size: 16px;
            font-weight: 700;
            border-radius: 8px;
            border: none;
            cursor: pointer;
        }
        
        .images-container {
            position: relative;
        }
        
        .image-left {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 409px;
            height: 438px;
            transform: rotate(-21.725deg);
        }
        
        .image-right {
            position: absolute;
            top: 68px;
            right: 503px;
            width: 272px;
            height: 383px;
            transform: rotate(-1.952deg);
        }
        
        .error-message {
            color: #ff0000;
            margin-bottom: 15px;
            text-align: center;
        }
        
        @media (max-width: 991px) {
            .header {
                padding-left: 40px;
                padding-right: 40px;
            }
            
            .main-content {
                padding-left: 40px;
                padding-right: 40px;
                flex-direction: column;
                gap: 40px;
            }
            
            .left-section {
                gap: 40px;
            }
            
            .main-heading {
                font-size: 40px;
                line-height: 48px;
            }
            
            .login-form-container {
                width: 100%;
            }
            
            .image-left, .image-right {
                display: none;
            }
        }
        
        @media (max-width: 640px) {
            .header {
                padding-left: 20px;
                padding-right: 20px;
            }
            
            .nav-menu {
                display: none;
            }
            
            .hamburger-menu {
                display: block;
                color: #04CD00;
            }
            
            .main-content {
                padding-left: 20px;
                padding-right: 20px;
            }
            
            .main-heading {
                font-size: 32px;
                line-height: 40px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-container">
            <img src="assets/logo.png" alt="TorollerCollective Logo" width="61" height="80">
            <div class="logo-text">TorollerCollective</div>
        </div>
        
        <div class="nav-menu">
            <div class="nav-links">
            <a class="nav-link" href="index.php">Home</a>
                <a class="nav-link" href="community.php">Community</a>
                <div class="nav-link-with-icon">
                    <a class="nav-link" href="shop.php">Shop</a>
                    <div>
                        <svg width="12" height="12" viewBox="0 0 66 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <text fill="#BDD3C6" xml:space="preserve" style="white-space: pre" font-family="DM Sans" font-size="18" letter-spacing="0px"><tspan x="0.475952" y="15.2126">Shop</tspan></text>
                            <path d="M53.3334 6.15796L59.1667 11.9913L65 6.15796" stroke="#211F54" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </div>
                </div>
                <div class="nav-link">Eventi</div>
            </div>
            
            <div class="auth-buttons">
                <a href="login.php" class="login-btn">Login</a>
                <a href="registrazione.php" class="get-started-btn">Get started</a>
            </div>
        </div>
        
        <div class="hamburger-menu">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="24" height="24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </div>
    </div>
    
    <div class="main-content">
        <div class="left-section">
            <div class="main-heading">Resta connesso alle tue passioni</div>
            
            <div class="features">
                <div class="feature">
                    <div>
                        <svg width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g clip-path="url(#clip0_16_1289)">
                                <path d="M13 26C20.1799 26 26 20.1799 26 13C26 5.8201 20.1799 0 13 0C5.8201 0 0 5.8201 0 13C0 20.1799 5.8201 26 13 26Z" fill="#04CD00"></path>
                                <path d="M7.11682 13.8405L10.4786 17.2023L18.8832 8.79773" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            </g>
                            <defs>
                                <clipPath id="clip0_16_1289">
                                    <rect width="26" height="26" fill="white"></rect>
                                </clipPath>
                            </defs>
                        </svg>
                    </div>
                    <div class="feature-text">La tua privacy e la nostra priorità</div>
                </div>
                
                <div class="feature">
                    <div>
                        <svg width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g clip-path="url(#clip0_16_1296)">
                                <path d="M13 26C20.1799 26 26 20.1799 26 13C26 5.8201 20.1799 0 13 0C5.8201 0 0 5.8201 0 13C0 20.1799 5.8201 26 13 26Z" fill="#04CD00"></path>
                                <path d="M7.11682 13.8405L10.4786 17.2023L18.8832 8.79773" fill="#04CD00"></path>
                                <path d="M7.11682 13.8405L10.4786 17.2023L18.8832 8.79773" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            </g>
                            <defs>
                                <clipPath id="clip0_16_1296">
                                    <rect width="26" height="26" fill="white"></rect>
                                </clipPath>
                            </defs>
                        </svg>
                    </div>
                    <div class="feature-text">utilizziamo sistemi di crittografia nel vostro rispetto</div>
                </div>
            </div>
        </div>
        
        <div class="login-form-container">
            <form class="login-form" method="POST" action="">
                
                    <div class="error-message"></div>
             
                
                <div class="form-group">
                    <label for="email" class="form-label">E-mail</label>
                    <input type="email" id="email" name="email" placeholder="example@email.com" class="form-input" value="" required>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" placeholder="Password" class="form-input" required>
                </div>
                
                <button type="submit" class="submit-btn">Get started</button>
            </form>
        </div>
    </div>
    
    <div class="images-container">
        <img src="assets/image-left.png" alt="" class="image-left">
        <img src="assets/image-right.png" alt="" class="image-right">
    </div>
</body>
</html>



