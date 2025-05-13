<?php
session_start();
include("conn.php");

// Verify if user is logged in
if (!isset($_SESSION['email']) || !isset($_SESSION['password'])) {
    header("Location: login.php");
    exit();
}

// Redirect admin users to admin.php
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
    header("Location: admin.php");
    exit();
}

$userEmail = $_SESSION['email'];
$error = "";
$success = "";

// Handle password change
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = connetti("toroller");
    $currentPassword = mysqli_real_escape_string($conn, $_POST["current_password"]);
    $newPassword = mysqli_real_escape_string($conn, $_POST["new_password"]);
    $confirmPassword = mysqli_real_escape_string($conn, $_POST["confirm_password"]);
    
    // Verify current password
    $query = "SELECT password FROM utente WHERE email = '$userEmail'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($currentPassword, $user['password'])) {
            if ($newPassword === $confirmPassword) {
                // Update password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateQuery = "UPDATE utente SET password = '$hashedPassword' WHERE email = '$userEmail'";
                
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
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilo Utente - TorollerCollective</title>
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

        .profile-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 30px;
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .profile-title {
            color: #04CD00;
            font-size: 24px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }

        .form-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            background-color: #04CD00;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }

        .error-message {
            color: #ff0000;
            margin-bottom: 15px;
        }

        .success-message {
            color: #04CD00;
            margin-bottom: 15px;
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
    </div>

    <div class="profile-container">
        <h2 class="profile-title">Modifica Password</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>

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

            <button type="submit" class="submit-btn">Aggiorna Password</button>
        </form>
    </div>
</body>
</html>