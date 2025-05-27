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
        $email = mysqli_real_escape_string($conn, $_SESSION['email']);
        $query_user = "SELECT * FROM utente WHERE email = '$email'";
        $result_user = mysqli_query($conn, $query_user);

        if (!$result_user) {
            throw new Exception("Errore nell'esecuzione della query utente: " . mysqli_error($conn));
        }

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
        mysqli_free_result($result_user);
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
            $email = mysqli_real_escape_string($conn, $_SESSION['email']);

            $updateQuery = "UPDATE utente SET nome = '$nome', cognome = '$cognome' WHERE email = '$email'";
            $result_update_profile = mysqli_query($conn, $updateQuery);

            if ($result_update_profile) {
                $success = "Profilo aggiornato con successo!";
                // Refresh user data by re-fetching
                $query_fetch_updated_user = "SELECT * FROM utente WHERE email = '$email'";
                $result_updated_user = mysqli_query($conn, $query_fetch_updated_user);
                $user = mysqli_fetch_assoc($result_updated_user); // Update $user variable
                mysqli_free_result($result_updated_user);
            } else {
                $error = "Errore durante l'aggiornamento del profilo: " . mysqli_error($conn);
            }
        } else {
            $error = "Impossibile aggiornare il profilo. Utente non loggato o errore di connessione.";
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        // Ensure $user is available and $conn is valid
        if ($user && $conn) {
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            $email = mysqli_real_escape_string($conn, $_SESSION['email']);

            // $user['password'] should now be correctly populated from the database query above
            if (password_verify($currentPassword, $user['password'])) {
                if ($newPassword === $confirmPassword) {
                    if (strlen($newPassword) < 8) {
                        $error = "La nuova password deve contenere almeno 8 caratteri.";
                    } else {
                        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                        $hashedPassword_escaped = mysqli_real_escape_string($conn, $hashedPassword);
                        $updateQueryPass = "UPDATE utente SET password = '$hashedPassword_escaped' WHERE email = '$email'";
                        $result_update_pass = mysqli_query($conn, $updateQueryPass);

                        if ($result_update_pass) {
                            $success = "Password aggiornata con successo!";
                            $_SESSION['password'] = $hashedPassword; // Update session password hash

                            // Re-fetch user data to update $user['password'] in the current script execution
                            $query_refetch_user_pass = "SELECT * FROM utente WHERE email = '$email'";
                            $result_refetch_user_pass = mysqli_query($conn, $query_refetch_user_pass);
                            $user = mysqli_fetch_assoc($result_refetch_user_pass); // Update
                            mysqli_free_result($result_refetch_user_pass);
                        }
                    }
                } else {
                    $error = "Le nuove password non coincidono.";
                }
            } else {
                $error = "La password attuale non è corretta.";
            }
        } else {
            $error = "Impossibile cambiare la password. Utente non loggato o errore di connessione.";
        }
    }
}

// HTML output
?>
<!DOCTYPE html>
<html lang="it">
<head>    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Il Mio Profilo - TorollerCollective</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/header.css">
    <link rel="stylesheet" href="style/profile.css">
    <link rel="stylesheet" href="style/footer.css">
</head>
<body>
    <?php include 'components/header.php'?>

    <div class="profile-container">
        <h1 class="profile-title">Il Mio Profilo</h1>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($user): ?>
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
                    <input type="password" id="current_password" name="current_password" class="form-input" required>
                </div>

                <div class="form-group">
                    <label for="new_password" class="form-label">Nuova Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-input" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">Conferma Nuova Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                </div>

                <button type="submit" name="change_password" class="submit-btn">Aggiorna Password</button>
            </form>        </div>
        <div class="profile-section">
            <a href="profile.php?logout=1" class="logout-link">Logout</a>
        </div>
        <?php endif; ?>
    </div>
    <script src="components/header.js"></script>
    <?php include 'components/footer.php'; ?>
</body>
</html>

