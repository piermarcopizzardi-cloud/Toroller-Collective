<?php
session_start();
include("conn.php");

// Controlla se l'utente è loggato
$isLoggedIn = isset($_SESSION['email']) && isset($_SESSION['password']);
$user = null; // Initialize user variable

// Se l'utente ha cliccato su logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

$conn = null;
$error = ""; // Initialize error message 
$success = ""; // Initialize success message

try {
    $conn = connetti("toroller_semplificato");
    if (!$conn) {
        throw new Exception("Errore di connessione al database");
    }

    // Fetch user data if logged in
    if ($isLoggedIn) {
        $email = $_SESSION['email'];
        $query_user = "SELECT * FROM utente WHERE email = ?";
        $stmt_user = mysqli_prepare($conn, $query_user);
        mysqli_stmt_bind_param($stmt_user, "s", $email);
        mysqli_stmt_execute($stmt_user);
        $result_user = mysqli_stmt_get_result($stmt_user);
        if ($row_user = mysqli_fetch_assoc($result_user)) {
            $user = $row_user;
            // Se l'utente è un amministratore, reindirizza ad admin.php
            if (isset($user['amministratore']) && $user['amministratore'] == 1) {
                header("Location: admin.php");
                exit();
            }
        } else {
            // User not found in DB with the session email
            session_destroy();
            header("Location: login.php?error=Sessione invalida o utente non trovato");
            exit();
        }
        mysqli_stmt_close($stmt_user);
    } else {
        // Not logged in, redirect to login
        header("Location: login.php?error=Accesso negato. Devi effettuare il login.");
        exit();
    }

} catch (Exception $e) {
    error_log("Errore database: " . $e->getMessage());
    $error = "Errore di connessione al database.";
}

// Gestione del profilo e cambio password
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile'])) {
        // Ensure $user is available and $conn is valid
        if ($user && $conn) {
            $nome = mysqli_real_escape_string($conn, $_POST['nome']);
            $cognome = mysqli_real_escape_string($conn, $_POST['cognome']); 
            
            $updateQuery = "UPDATE utente SET nome = ?, cognome = ? WHERE email = ?";
            $stmt_update_profile = mysqli_prepare($conn, $updateQuery);
            mysqli_stmt_bind_param($stmt_update_profile, "sss", $nome, $cognome, $email);

            if (mysqli_stmt_execute($stmt_update_profile)) {
                $success = "Profilo aggiornato con successo!";
                // Refresh user data by re-fetching
                $stmt_fetch_updated_user = mysqli_prepare($conn, "SELECT * FROM utente WHERE email = ?");
                mysqli_stmt_bind_param($stmt_fetch_updated_user, "s", $email);
                mysqli_stmt_execute($stmt_fetch_updated_user);
                $result_updated_user = mysqli_stmt_get_result($stmt_fetch_updated_user);
                $user = mysqli_fetch_assoc($result_updated_user); // Update $user variable
                mysqli_stmt_close($stmt_fetch_updated_user);
            } else {
                $error = "Errore durante l'aggiornamento del profilo: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt_update_profile);
        } else {
            $error = "Impossibile aggiornare il profilo. Utente non loggato o errore di connessione.";
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        // Ensure $user is available and $conn is valid
        if ($user && $conn) {
            $currentPassword = mysqli_real_escape_string($conn, $_POST['current_password']);
            $newPassword = mysqli_real_escape_string($conn, $_POST['new_password']);
            $confirmPassword = mysqli_real_escape_string($conn, $_POST['confirm_password']);

            // $user['password'] should now be correctly populated from the database query above
            if (password_verify($currentPassword, $user['password'])) {
                if ($newPassword === $confirmPassword) {
                    if (strlen($newPassword) < 8) {
                        $error = "La nuova password deve contenere almeno 8 caratteri.";
                    } else {
                        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                        $updateQueryPass = "UPDATE utente SET password = ? WHERE email = ?";
                        $stmt_update_pass = mysqli_prepare($conn, $updateQueryPass);
                        mysqli_stmt_bind_param($stmt_update_pass, "ss", $hashedPassword, $email);
                        
                        if (mysqli_stmt_execute($stmt_update_pass)) {
                            $success = "Password aggiornata con successo!";
                            $_SESSION['password'] = $hashedPassword;
                            // Re-fetch user data
                            $stmt_refetch_user_pass = mysqli_prepare($conn, "SELECT * FROM utente WHERE email = ?");
                            mysqli_stmt_bind_param($stmt_refetch_user_pass, "s", $email);
                            mysqli_stmt_execute($stmt_refetch_user_pass);
                            $result_refetch_user_pass = mysqli_stmt_get_result($stmt_refetch_user_pass);
                            $user = mysqli_fetch_assoc($result_refetch_user_pass);
                            mysqli_stmt_close($stmt_refetch_user_pass);
                        } else {
                            $error = "Errore durante l'aggiornamento della password: " . mysqli_error($conn);
                        }
                        mysqli_stmt_close($stmt_update_pass);
                    }
                } else {
                    $error = "Le nuove password non corrispondono.";
                }
            } else {
                $error = "Password attuale non corretta.";
            }
        } else {
             $error = "Impossibile cambiare la password. Utente non loggato o errore di connessione.";
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
    <?php $basePath = dirname($_SERVER['PHP_SELF']); if ($basePath == '/') $basePath = ''; ?>
    <meta name="base-path" content="<?php echo rtrim(dirname($_SERVER['PHP_SELF']), '/'); ?>">    
    <link rel="stylesheet" href="<?php echo $basePath; ?>/style/header.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>/style/admin.css">
</head>
<body class="admin-page">
    <?php include 'components/header.php'?>

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
                    <input type="text" name="nome" class="form-input" value="<?php echo isset($user['nome']) ? htmlspecialchars($user['nome']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Cognome</label>
                    <input type="text" name="cognome" class="form-input" value="<?php echo isset($user['cognome']) ? htmlspecialchars($user['cognome']) : ''; ?>" required>
                </div>                    
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-input" value="<?php echo isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?>" disabled>
                </div>

                <button type="submit" name="update_profile" class="submit-btn">Aggiorna Profilo</button>
            </form>
        </div>

        <div class="profile-section">
            <h2>Cambia Password</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="current_password" class="form-label">Password Attuale</label>
                    <input type="password" id="current_password" name="current_password" class="form-input" 
                           required autocomplete="current-password">
                </div>

                <div class="form-group">
                    <label for="new_password" class="form-label">Nuova Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-input" 
                           required autocomplete="new-password"
                           pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                           title="La password deve contenere almeno 8 caratteri, inclusi numeri, lettere maiuscole e minuscole">
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">Conferma Nuova Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                           required autocomplete="new-password">
                </div>

                <button type="submit" name="change_password" class="submit-btn">Cambia Password</button>
            </form>

            <div class="password-requirements">
                <h3>Requisiti password:</h3>
                <ul>
                    <li>Minimo 8 caratteri</li>
                    <li>Almeno una lettera maiuscola</li>
                    <li>Almeno una lettera minuscola</li>
                    <li>Almeno un numero</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
