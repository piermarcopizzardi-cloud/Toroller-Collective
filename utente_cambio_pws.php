<?php
session_start();
include("conn.php");

// Controlla se l'utente è loggato
$isLoggedIn = isset($_SESSION['email']) && isset($_SESSION['password']);

// Se l'utente ha cliccato su logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

$conn = null;
try {
    $conn = connetti("toroller");
    if (!$conn) {
        throw new Exception("Errore di connessione al database");
    }
} catch (Exception $e) {
    error_log("Errore database: " . $e->getMessage());
}

// Ottieni le informazioni dell'utente se è loggato
$userEmail = '';
$userName = '';
$cartItems = [];
$cartTotal = 0;

if ($isLoggedIn && $conn) {
    $email = mysqli_real_escape_string($conn, $_SESSION['email']);
    $query = "SELECT nome, email FROM utente WHERE email = '$email'";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $userEmail = $user['email'];
        $userName = $user['nome'];
    }

    // Ottieni il contenuto del carrello per l'utente loggato
    $cartQuery = "SELECT c.id, c.quantita, p.tipologia as name, p.prezzo as price, p.id as product_id 
                 FROM carrello c 
                 JOIN prodotti p ON c.id_prodotto = p.id 
                 WHERE c.email_utente = '$email'";
    $cartResult = mysqli_query($conn, $cartQuery);
    
    if ($cartResult) {
        while ($row = mysqli_fetch_assoc($cartResult)) {
            $cartItems[] = $row;
            $cartTotal += $row['price'] * $row['quantita'];
        }
    }
}

if ($conn) {
    mysqli_close($conn);
}

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
    <meta name="description" content="Cambia la tua password su TorollerCollective">
    <title>Cambio Password - TorollerCollective</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <?php $basePath = dirname($_SERVER['PHP_SELF']); if ($basePath == '/') $basePath = ''; ?>
    <meta name="base-path" content="<?php echo rtrim(dirname($_SERVER['PHP_SELF']), '/'); ?>">
    <link href="<?php echo $basePath; ?>/style/cambio_password.css" rel="stylesheet">
    <link rel="icon" href="<?php echo $basePath; ?>/assets/logo1.jpg" type="image/jpeg">
    <link href="<?php echo $basePath; ?>/style/header.css" rel="stylesheet">
    <link href="<?php echo $basePath; ?>/style/cart.css" rel="stylesheet">
</head>
<body>
    <?php include 'components/header.php'?>

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
        
        <div class="auth-buttons">
            <?php if ($isLoggedIn): ?>
            <div class="user-menu">
                <a href="utente_cambio_pws.php" class="user-email"><?php echo htmlspecialchars($userEmail); ?></a>
                <a href="?logout=1" class="logout-btn">Logout</a>
            </div>
            <?php else: ?>
            <a href="login.php" class="login-btn">Login</a>
            <a href="registrazione.php" class="get-started-btn">Get started</a>
            <?php endif; ?>
        </div>
    </div>

    <main class="profile-container">
        <h1 class="profile-title">Modifica Password</h1>
        
        <?php if (!empty($error)): ?>
            <div class="error-message" role="alert">
                <span class="visually-hidden">Errore: </span>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success-message" role="alert">
                <span class="visually-hidden">Successo: </span>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" aria-labelledby="form-title">
            <h2 id="form-title" class="visually-hidden">Form di cambio password</h2>
            
            <div class="form-group">
                <label for="current-password" class="form-label">Password Attuale</label>
                <input type="password" 
                       id="current-password" 
                       name="current_password" 
                       class="form-input" 
                       required 
                       autocomplete="current-password"
                       aria-required="true">
            </div>

            <div class="form-group">
                <label for="new-password" class="form-label">Nuova Password</label>
                <input type="password" 
                       id="new-password" 
                       name="new_password" 
                       class="form-input" 
                       required 
                       autocomplete="new-password"
                       aria-required="true"
                       pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                       title="La password deve contenere almeno 8 caratteri, inclusi numeri, lettere maiuscole e minuscole">
            </div>

            <div class="form-group">
                <label for="confirm-password" class="form-label">Conferma Nuova Password</label>
                <input type="password" 
                       id="confirm-password" 
                       name="confirm_password" 
                       class="form-input" 
                       required
                       autocomplete="new-password"
                       aria-required="true">
            </div>

            <button type="submit" class="submit-btn">
                Aggiorna Password
            </button>

            <div class="password-requirements">
                <h3>Requisiti password:</h3>
                <ul>
                    <li>Minimo 8 caratteri</li>
                    <li>Almeno una lettera maiuscola</li>
                    <li>Almeno una lettera minuscola</li>
                    <li>Almeno un numero</li>
                </ul>
            </div>
        </form>
    </main>

    <script src="<?php echo $basePath; ?>/components/header.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hamburger = document.querySelector('.hamburger-menu');
            const closeMenu = document.querySelector('.close-menu');
            const mobileMenu = document.querySelector('.mobile-menu');
            const mobileLinks = document.querySelectorAll('.mobile-menu .nav-link, .mobile-menu .auth-buttons a');

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