<?php
session_start();
include("conn.php");

// Controlla se l'utente è loggato
$isLoggedIn = isset($_SESSION['email']) && isset($_SESSION['password']); // Email is still used for session login tracking as per original logic

// Se l'utente ha cliccato su logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

$conn = null;
try {
    $conn = connetti("toroller_semplificato"); // Updated DB name
    if (!$conn) {
        throw new Exception("Errore di connessione al database");
    }
} catch (Exception $e) {
    error_log("Errore database: " . $e->getMessage());
}

// Ottieni le informazioni dell'utente se è loggato
$userEmail = '';
$userNameDisplay = ''; // Changed variable name for clarity

if ($isLoggedIn && $conn) {
    $email_session = mysqli_real_escape_string($conn, $_SESSION['email']);
    // Fetch username based on email from session for display purposes if needed
    $query = "SELECT username, nome FROM utente WHERE email = '$email_session'"; 
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $userEmail = $email_session; // Keep email for session context
        $userNameDisplay = $user['username']; // Display username
    }
    // Removed cart logic
}

if ($conn) {
    mysqli_close($conn);
}

// Initialize variables
$name = "";
$surname = "";
$username_form = ""; // New variable for username from form
$email_form = ""; // New variable for email from form
$error = "";
$success = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = trim($_POST["name"]);
    $surname = trim($_POST["surname"]);
    $username_form = trim($_POST["username"]); // Get username
    $email_form = trim($_POST["email"]); // Get email
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    
    // Basic validation
    if (empty($name) || empty($surname) || empty($username_form) || empty($email_form) || empty($password) || empty($confirm_password)) {
        $error = "Tutti i campi sono obbligatori.";
    } elseif (!filter_var($email_form, FILTER_VALIDATE_EMAIL)) {
        $error = "Formato email non valido.";
    } elseif (strlen($password) < 8) {
        $error = "La password deve contenere almeno 8 caratteri.";
    } elseif ($password !== $confirm_password) {
        $error = "Le password non corrispondono.";
    } else {
        // Database connection
        $conn = connetti("toroller_semplificato"); // Updated DB name
        
        // Check connection
        if (!$conn) {
            $error = "Errore di connessione al database";
        } else {
            // Check if username already exists
            $username_check = mysqli_real_escape_string($conn, $username_form);
            $query_check_username = "SELECT username FROM utente WHERE username = '$username_check'";
            $result_check_username = mysqli_query($conn, $query_check_username);

            // Check if email already exists
            $email_check = mysqli_real_escape_string($conn, $email_form);
            $query_check_email = "SELECT email FROM utente WHERE email = '$email_check'";
            $result_check_email = mysqli_query($conn, $query_check_email);
            
            if (!$result_check_username || !$result_check_email) {
                $error = "Errore durante la verifica dell'utente: " . mysqli_error($conn);
            } else if (mysqli_num_rows($result_check_username) > 0) {
                $error = "Username già registrato. Prova con un altro username.";
            } else if (mysqli_num_rows($result_check_email) > 0) {
                $error = "Email già registrata. Prova con un'altra email.";
            }
            else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $name_db = mysqli_real_escape_string($conn, $name);
                $surname_db = mysqli_real_escape_string($conn, $surname);
                $username_db = mysqli_real_escape_string($conn, $username_form);
                $email_db = mysqli_real_escape_string($conn, $email_form);
                
                // Insert new user (removed data_nascita)
                $query_insert = "INSERT INTO utente (nome, cognome, username, email, password) VALUES ('$name_db', '$surname_db', '$username_db', '$email_db', '$hashed_password')";
                
                error_log("Query di registrazione: " . $query_insert);
                
                if (!mysqli_query($conn, $query_insert)) {
                    $error = "Errore durante la registrazione: " . mysqli_error($conn);
                    error_log("Errore MySQL: " . mysqli_error($conn));
                } else {
                    error_log("Registrazione completata con successo");
                    // Set success message in session so it persists after redirect
                    $_SESSION['registration_success'] = true;
                    header("Location: login.php"); // Redirect to login page
                    exit();
                }
            }
            mysqli_close($conn);
        }
    }
}

// Check for success message from registration
if (isset($_SESSION['registration_success'])) {
    // $success = "Registrazione completata con successo! Ora puoi effettuare il login."; // This message was originally intended for the registration page, but user is redirected.
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
    <link rel="stylesheet" href="<?php echo $basePath; ?>/style/header.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>/style/registrazione.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>/style/footer.css">
</head>
<body>
    <?php include 'components/header.php'?>
    
    <div class="main-content">
        <div class="registration-form-container">
            <h1 class="main-heading">Unisciti a noi</h1> <!-- Simplified heading -->
            
            <form class="registration-form" method="POST" action="<?php echo $_SERVER["PHP_SELF"]; ?>">
                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="10" cy="10" r="10" fill="#FFE5E5"/>
                            <path d="M10 5V11M10 13V15" stroke="#DC3545" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php /* Success message is handled by redirecting to login now
                <?php if (!empty($success)): ?>
                    <div class="success-message">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="10" cy="10" r="10" fill="#E8F5E9"/>
                            <path d="M6 10L9 13L14 7" stroke="#28A745" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                */ ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name" class="form-label">Nome</label>
                        <input type="text" id="name" name="name" placeholder="Il tuo nome" class="form-input" value="<?php echo $name; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="surname" class="form-label">Cognome</label>
                        <input type="text" id="surname" name="surname" placeholder="Il tuo cognome" class="form-input" value="<?php echo $surname; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                     <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" id="username" name="username" placeholder="Scegli un username" class="form-input" value="<?php echo $username_form; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" id="email" name="email" placeholder="example@email.com" class="form-input" value="<?php echo $email_form; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" placeholder="Min. 8 caratteri" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Conferma Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Ripeti la password" class="form-input" required>
                    </div>
                </div>

                <button type="submit" class="submit-button">Registrati</button>
            </form>
            
            <p class="login-link">Hai già un account? <a href="login.php">Accedi</a></p>
    </div>
    </div>
    
    <script src="<?php echo $basePath; ?>/components/header.js?v=<?php echo time(); ?>"></script>
    <?php include 'components/footer.php'; ?>
</body>
</html>
