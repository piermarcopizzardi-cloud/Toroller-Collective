<?php
session_start();
include("conn.php");

// Verify if user is logged in
if (!isset($_SESSION['email']) || !isset($_SESSION['password'])) {
    header("Location: login.php");
    exit();
}

$userEmail = $_SESSION['email'];
$error = "";
$success = "";

// Get user data
$conn = connetti("toroller");
$email = mysqli_real_escape_string($conn, $_SESSION['email']);
$query = "SELECT * FROM utente WHERE email = '$email'";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);


// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile'])) {
        $nome = mysqli_real_escape_string($conn, $_POST['nome']);
        $cognome = mysqli_real_escape_string($conn, $_POST['cognome']);
        $dataNascita = mysqli_real_escape_string($conn, $_POST['data_nascita']);
        
        $updateQuery = "UPDATE utente SET nome = '$nome', cognome = '$cognome', data_nascita = '$dataNascita' WHERE email = '$email'";
        if (mysqli_query($conn, $updateQuery)) {
            $success = "Profilo aggiornato con successo!";
            // Refresh user data
            $result = mysqli_query($conn, $query);
            $user = mysqli_fetch_assoc($result);
        } else {
            $error = "Errore durante l'aggiornamento del profilo.";
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $currentPassword = mysqli_real_escape_string($conn, $_POST['current_password']);
        $newPassword = mysqli_real_escape_string($conn, $_POST['new_password']);
        $confirmPassword = mysqli_real_escape_string($conn, $_POST['confirm_password']);
        
        if (password_verify($currentPassword, $user['password'])) {
            if ($newPassword === $confirmPassword) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateQuery = "UPDATE utente SET password = '$hashedPassword' WHERE email = '$email'";
                
                if (mysqli_query($conn, $updateQuery)) {
                    $success = "Password aggiornata con successo!";
                    $_SESSION['password'] = $hashedPassword;
                } else {
                    $error = "Errore durante l'aggiornamento della password.";
                }
            } else {
                $error = "Le nuove password non corrispondono.";
            }
        } else {
            $error = "Password attuale non corretta.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Il Mio Profilo - TorollerCollective</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: #f5f5f5;
        }

        .header {
            width: 100%;
            height: 118px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 110px;
            background-color: #ffffff;
            margin-bottom: 40px;
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
            text-decoration: none;
        }

        .nav-link.active {
            color: #04CD00;
            font-weight: 600;
        }

        .profile-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .profile-title {
            color: #04CD00;
            font-size: 32px;
            margin-bottom: 30px;
        }

        .profile-section {
            background: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        .submit-btn {
            background-color: #04CD00;
            color: #ffffff;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
        }

        .error-message {
            color: #ff0000;
            margin-bottom: 20px;
            padding: 12px;
            background-color: #ffe6e6;
            border-radius: 4px;
        }

        .success-message {
            color: #04CD00;
            margin-bottom: 20px;
            padding: 12px;
            background-color: #e6ffe6;
            border-radius: 4px;
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

        .hamburger-menu {
            display: none;
        }

        @media (max-width: 991px) {
            .header {
                padding: 0 40px;
            }

            .profile-container {
                padding: 0 40px;
            }
        }
        
        @media (max-width: 640px) {
            .header {
                padding: 0 20px;
            }

            .nav-menu {
                display: none;
            }

            .hamburger-menu {
                display: block;
                color: #04CD00;
                cursor: pointer;
                z-index: 1001;
            }

            .profile-container {
                padding: 0 20px;
            }
        }

        .mobile-menu {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.98);
            z-index: 1000;
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 24px;
        }

        .mobile-menu.active {
            display: flex;
        }

        .mobile-menu .nav-link {
            font-size: 24px;
            padding: 12px;
        }

        .mobile-menu .auth-buttons {
            flex-direction: column;
            margin-top: 24px;
        }

        .close-menu {
            position: absolute;
            top: 32px;
            right: 32px;
            cursor: pointer;
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
                </div>
                <a class="nav-link" href="eventi.php">Eventi</a>
            </div>
            
            <div class="user-menu">
                <span class="user-email"><?php echo htmlspecialchars($userEmail); ?></span>
                <a href="index.php?logout=1" class="logout-btn">Logout</a>
            </div>
        </div>

        <div class="hamburger-menu">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="24" height="24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </div>
    </div>

    <div class="mobile-menu">
        <div class="close-menu">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="24" height="24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </div>
        <a class="nav-link" href="index.php">Home</a>
        <a class="nav-link" href="community.php">Community</a>
        <a class="nav-link" href="shop.php">Shop</a>
        <a class="nav-link" href="eventi.php">Eventi</a>
        
        <div class="user-menu">
            <span class="user-email"><?php echo htmlspecialchars($userEmail); ?></span>
            <a href="index.php?logout=1" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="profile-container">
        <h1 class="profile-title">Il Mio Profilo</h1>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="profile-section">
            <h2>Informazioni Personali</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Nome</label>
                    <input type="text" name="nome" class="form-input" value="<?php echo htmlspecialchars($user['nome']); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Cognome</label>
                    <input type="text" name="cognome" class="form-input" value="<?php echo htmlspecialchars($user['cognome']); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-input" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                </div>

                <div class="form-group">
                    <label class="form-label">Data di Nascita</label>
                    <input type="date" name="data_nascita" class="form-input" value="<?php echo htmlspecialchars($user['data_nascita']); ?>" required>
                </div>

                <button type="submit" name="update_profile" class="submit-btn">Aggiorna Profilo</button>
            </form>
        </div>

        <div class="profile-section">
            <h2>Cambia Password</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Password Attuale</label>
                    <input type="password" name="current_password" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Nuova Password</label>
                    <input type="password" name="new_password" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Conferma Nuova Password</label>
                    <input type="password" name="confirm_password" class="form-input" required>
                </div>

                <button type="submit" name="change_password" class="submit-btn">Cambia Password</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hamburger = document.querySelector('.hamburger-menu');
            const closeMenu = document.querySelector('.close-menu');
            const mobileMenu = document.querySelector('.mobile-menu');
            const mobileLinks = document.querySelectorAll('.mobile-menu .nav-link, .mobile-menu .user-menu a');

            function toggleMenu() {
                mobileMenu.classList.toggle('active');
                document.body.style.overflow = mobileMenu.classList.contains('active') ? 'hidden' : '';
            }

            hamburger.addEventListener('click', toggleMenu);
            closeMenu.addEventListener('click', toggleMenu);

            // Close menu when clicking on links
            mobileLinks.forEach(link => {
                link.addEventListener('click', toggleMenu);
            });
        });
    </script>
</body>
</html>