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

// Initialize variables
$name = "";
$surname = "";
$email = "";
$birthdate = "";
$error = "";
$success = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = trim($_POST["name"]);
    $surname = trim($_POST["surname"]);
    $email = trim($_POST["email"]);
    $birthdate = $_POST["birthdate"];
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    
    // Basic validation
    if (empty($name) || empty($surname) || empty($email) || empty($birthdate) || empty($password) || empty($confirm_password)) {
        $error = "Tutti i campi sono obbligatori.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Formato email non valido.";
    } elseif (strlen($password) < 8) {
        $error = "La password deve contenere almeno 8 caratteri.";
    } elseif ($password !== $confirm_password) {
        $error = "Le password non corrispondono.";
    } else {
        // Database connection
        $conn = connetti("toroller");
        
        // Check connection
        if (!$conn) {
            $error = "Errore di connessione al database";
        } else {
            // Check if email already exists
            $email = mysqli_real_escape_string($conn, $email);
            $query = "SELECT email FROM utente WHERE email = '$email'";
            $result = mysqli_query($conn, $query);
            
            if (!$result) {
                $error = "Errore durante la verifica dell'email: " . mysqli_error($conn);
            } else if (mysqli_num_rows($result) > 0) {
                $error = "Email già registrata. Prova con un'altra email.";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $name = mysqli_real_escape_string($conn, $name);
                $surname = mysqli_real_escape_string($conn, $surname);
                $birthdate = mysqli_real_escape_string($conn, $birthdate);
                
                // Insert new user
                $query = "INSERT INTO utente (nome, cognome, email, password, data_nascita) VALUES ('$name', '$surname', '$email', '$hashed_password', '$birthdate')";
                
                error_log("Query di registrazione: " . $query);
                
                if (!mysqli_query($conn, $query)) {
                    $error = "Errore durante la registrazione: " . mysqli_error($conn);
                    error_log("Errore MySQL: " . mysqli_error($conn));
                } else {
                    error_log("Registrazione completata con successo");
                    // Set success message in session so it persists after redirect
                    $_SESSION['registration_success'] = true;
                    header("Location: login.php");
                    exit();
                }
            }
            mysqli_close($conn);
        }
    }
}

// Check for success message from registration
if (isset($_SESSION['registration_success'])) {
   
    unset($_SESSION['registration_success']); // Clear the message
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione - TorollerCollective</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap" rel="stylesheet">
    <?php $basePath = dirname($_SERVER['PHP_SELF']); if ($basePath == '/') $basePath = ''; ?>
    <meta name="base-path" content="<?php echo rtrim(dirname($_SERVER['PHP_SELF']), '/'); ?>">
    <link href="<?php echo $basePath; ?>/style/registrazione.css" rel="stylesheet">
    <link href="<?php echo $basePath; ?>/style/header.css" rel="stylesheet">
    <link href="<?php echo $basePath; ?>/style/cart.css" rel="stylesheet">
</head>
<body>
    <?php include 'components/header.php'?>
    
    <div class="main-content">
        <div class="registration-form-container">
            <h1 class="main-heading">Unisciti alla nostra community</h1>
            
            <form class="registration-form" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
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
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name" class="form-label">Nome</label>
                        <input type="text" id="name" name="name" placeholder="Il tuo nome" class="form-input" value="<?php echo htmlspecialchars($name); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="surname" class="form-label">Cognome</label>
                        <input type="text" id="surname" name="surname" placeholder="Il tuo cognome" class="form-input" value="<?php echo htmlspecialchars($surname); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" id="email" name="email" placeholder="example@email.com" class="form-input" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="birthdate" class="form-label">Data di nascita</label>
                        <input type="date" id="birthdate" name="birthdate" class="form-input" value="<?php echo htmlspecialchars($birthdate); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" placeholder="Almeno 8 caratteri" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Conferma Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Conferma la tua password" class="form-input" required>
                    </div>
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

        // function toggleCart() {
        //     const cartPopup = document.getElementById('cartPopup');
        //     cartPopup.classList.toggle('active');
        // }
    </script>
</body>
</html>
