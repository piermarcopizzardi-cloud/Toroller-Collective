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

// Se l'utente è già loggato, redirect a index.php
if(isset($_SESSION['email']) && isset($_SESSION['password']))
{
    header("Location: index.php");
    exit();
}

$error = "";

// Procedi solo se il form è stato inviato
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = connetti("toroller");
    
    if (!$conn) {
        $error = "Errore di connessione al database";
    } else {
        $email = mysqli_real_escape_string($conn, $_POST["email"]);
        $password = mysqli_real_escape_string($conn, $_POST["password"]);
        
        if (empty($email) || empty($password)) {
            $error = "Per favore, compila tutti i campi.";
        } else {
            // Verifica se l'utente esiste
            $sql = "SELECT * FROM utente WHERE email = '$email'";
            $ris = mysqli_query($conn, $sql);
            
            if (!$ris) {
                $error = "Errore durante la verifica dell'utente: " . mysqli_error($conn);
            } else {
                $num_rows = mysqli_num_rows($ris);
                if ($num_rows <= 0) {
                    $error = "Utente non trovato. Registrati per continuare.";
                } else {
                    // Verifica la password
                    $user = mysqli_fetch_assoc($ris);
                    if (password_verify($password, $user['password'])) {
                        // Login successful
                        $_SESSION['email'] = $email;
                        $_SESSION['password'] = $user['password']; // Store hashed password
                        $_SESSION['is_admin'] = $user['amministratore'] == 1; // Salva se l'utente è admin
                        header("Location: index.php");
                        exit();
                    } else {
                        $error = "Password non corretta.";
                    }
                }
            }
        }
        
        mysqli_close($conn);
    }
}

// Check for success message from registration
if (isset($_SESSION['registration_success'])) {
    $success = "Registrazione completata con successo! Effettua il login.";
    unset($_SESSION['registration_success']); // Clear the message
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TorollerCollective</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap" rel="stylesheet">
    <link href="style/login.css" rel="stylesheet">
     <link href="style/header.css" rel="stylesheet">
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
        <a class="nav-link active" href="eventi.php">Eventi</a>
        
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
    
    <div class="main-content">
        <div class="left-section">
            <h1 class="main-heading">Accedi al tuo account</h1>
            
            <div class="features">
                <div class="feature">
                    <div class="feature-icon">
                        <svg width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="13" cy="13" r="13" fill="#04CD00"/>
                            <path d="M7.11682 13.8405L10.4786 17.2023L18.8832 8.79773" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <p class="feature-text">Accedi in modo sicuro al tuo account</p>
                </div>

                <div class="feature">
                    <div class="feature-icon">
                        <svg width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="13" cy="13" r="13" fill="#04CD00"/>
                            <path d="M13 7V19M7 13H19" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <p class="feature-text">Gestisci i tuoi ordini e le tue attività</p>
                </div>

                <div class="feature">
                    <div class="feature-icon">
                        <svg width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="13" cy="13" r="13" fill="#04CD00"/>
                            <path d="M17 9C17 7.34315 15.2091 6 13 6C10.7909 6 9 7.34315 9 9C9 10.6569 10.7909 12 13 12C15.2091 12 17 13.3431 17 15C17 16.6569 15.2091 18 13 18C10.7909 18 9 16.6569 9 15" stroke="white" stroke-width="2" stroke-linecap="round"/>
                            <path d="M13 5V7M13 17V19" stroke="white" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <p class="feature-text">Partecipa alla community e agli eventi</p>
                </div>
            </div>
        </div>

        <div class="login-form-container">
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="10" cy="10" r="10" fill="#FFE5E5"/>
                        <path d="M10 5V11M10 13V15" stroke="#DC3545" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="success-message">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="10" cy="10" r="10" fill="#E8F5E9"/>
                        <path d="M6 10L9 13L14 7" stroke="#28A745" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form class="login-form" method="POST" action="">
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-input" placeholder="Inserisci la tua email" required>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-input" placeholder="Inserisci la tua password" required>
                </div>

                <button type="submit" class="submit-btn">Accedi</button>

                <div class="form-footer">
                    <p>Non hai un account? <a href="registrazione.php" class="link-primary">Registrati</a></p>
                </div>
            </form>
        </div>
    </div>    
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

        function toggleCart() {
            const cartPopup = document.getElementById('cartPopup');
            cartPopup.classList.toggle('active');
        }
    </script>
</body>
</html>

