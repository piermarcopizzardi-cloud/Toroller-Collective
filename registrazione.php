<?php
session_start();
include("conn.php");

// Se l'utente ha cliccato su logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Se l'utente è già loggato, redirect a index.php
if(isset($_SESSION['email']) && isset($_SESSION['password']))
{
    header("Location: index.php");
    exit();
}

// Controlla se l'utente è loggato
$isLoggedIn = isset($_SESSION['email']) && isset($_SESSION['password']);

// Ottieni le informazioni dell'utente se è loggato
$userEmail = '';
$userName = '';
if ($isLoggedIn) {
    $conn = connetti("toroller");
    $email = mysqli_real_escape_string($conn, $_SESSION['email']);
    $query = "SELECT nome, email FROM utente WHERE email = '$email'";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $userEmail = $user['email'];
        $userName = $user['nome'];
    }
    mysqli_close($conn);
}

// Initialize variables
$name = "";
$email = "";
$error = "";
$success = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    
    // Basic validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Tutti i campi sono obbligatori.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Formato email non valido.";
    } elseif (strlen($password) < 8) {
        $error = "La password deve contenere almeno 8 caratteri.";
    } elseif ($password !== $confirm_password) {
        $error = "Le password non corrispondono.";
    } else {
        // Database connection
        $conn = new mysqli("localhost", "username", "password", "toroller"); // Replace with your actual database credentials
        
        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Email già registrata. Prova con un'altra email.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Prepare SQL statement to insert new user
            $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $hashed_password);
            
            // Execute the statement
            if ($stmt->execute()) {
                $success = "Registrazione completata con successo! Ora puoi effettuare il login.";
                // Clear form data after successful registration
                $name = "";
                $email = "";
            } else {
                $error = "Errore durante la registrazione. Riprova più tardi.";
            }
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
    <title>Registrazione - TorollerCollective</title>
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
        
        .registration-form-container {
            width: 506px;
            background-color: #ffffff;
            border-radius: 30px;
            box-shadow: 0px 4px 4px 0px rgba(0, 0, 0, 0.25);
            padding: 40px;
        }
        
        .registration-form {
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
        
        .success-message {
            color: #04CD00;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .login-link {
            text-align: center;
            margin-top: 15px;
        }
        
        .login-link a {
            color: #04CD00;
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
            text-decoration: underline;
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
            
            .registration-form-container {
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
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 8px 16px;
            border: 1px solid #7FE47E;
            border-radius: 30px;
        }
        
        .user-email {
            color: #04CD00;
            font-size: 16px;
            font-weight: 600;
        }
        
        .logout-btn {
            color: #BDD3C6;
            text-decoration: none;
            font-size: 14px;
        }
        
        .logout-btn:hover {
            color: #04CD00;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-container">
            <img src="assets/logo1.jpg" alt="TorollerCollective Logo" width="80" height="80" style="object-fit: contain;">
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
                <div class="nav-link" href="eventi.php">Eventi</div>
            </div>
            
            <div class="auth-buttons">
                <?php if ($isLoggedIn): ?>
                <div class="user-menu">
                    <span class="user-email"><?php echo htmlspecialchars($userEmail); ?></span>
                    <a href="?logout=1" class="logout-btn">Logout</a>
                </div>
                <?php else: ?>
                <a href="login.php" class="login-btn">Login</a>
                <a href="registrazione.php" class="get-started-btn">Get started</a>
                <?php endif; ?>
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
            <div class="main-heading">Unisciti alla nostra community</div>
            
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
        
        <div class="registration-form-container">
            <form class="registration-form" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <?php if (!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="name" class="form-label">Nome completo</label>
                    <input type="text" id="name" name="name" placeholder="Il tuo nome" class="form-input" value="<?php echo htmlspecialchars($name); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">E-mail</label>
                    <input type="email" id="email" name="email" placeholder="example@email.com" class="form-input" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" placeholder="Almeno 8 caratteri" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Conferma Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Conferma la tua password" class="form-input" required>
                </div>
                
                <button type="submit" class="submit-btn">Registrati</button>
                
                <div class="login-link">
                    Hai già un account? <a href="login.php">Accedi</a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="images-container">
        <img src="assets/image-left.png" alt="" class="image-left">
        <img src="assets/image-right.png" alt="" class="image-right">
    </div>
</body>
</html>
