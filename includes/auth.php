<?php
/**
 * Funzioni relative all'autenticazione e alla gestione degli utenti
 */
require_once 'utility.php';

/**
 * Verifica se un utente è amministratore
 * @param mysqli $conn Connessione al database
 * @param string $email Email dell'utente
 * @return bool True se l'utente è amministratore, false altrimenti
 */
function isUserAdmin($conn, $email) {
    $query = "SELECT amministratore FROM utente WHERE email = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        error_log("isUserAdmin - mysqli_prepare failed: " . mysqli_error($conn));
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "s", $email);
    
    if (!mysqli_stmt_execute($stmt)) {
        error_log("isUserAdmin - mysqli_stmt_execute failed: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return false;
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    return $user && isset($user['amministratore']) && $user['amministratore'] == 1;
}

/**
 * Ottiene i dati di un utente dal database
 * @param mysqli $conn Connessione al database
 * @param string $email Email dell'utente
 * @return array|null Dati dell'utente o null se non trovato
 */
function getUserData($conn, $email) {
    $query = "SELECT * FROM utente WHERE email = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        error_log("getUserData - mysqli_prepare failed: " . mysqli_error($conn));
        return null;
    }
    
    mysqli_stmt_bind_param($stmt, "s", $email);
    
    if (!mysqli_stmt_execute($stmt)) {
        error_log("getUserData - mysqli_stmt_execute failed: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return null;
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $userData = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    return $userData;
}

/**
 * Effettua il login di un utente
 * @param mysqli $conn Connessione al database
 * @param string $email Email dell'utente
 * @param string $password Password dell'utente
 * @return array Risultato del login con status e messaggio
 */
function loginUser($conn, $email, $password) {
    $email = sanitizeInput($email);
    
    if (!isValidEmail($email)) {
        return ['status' => false, 'message' => 'Email non valida'];
    }
    
    $query = "SELECT * FROM utente WHERE email = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        error_log("loginUser - mysqli_prepare failed: " . mysqli_error($conn));
        return ['status' => false, 'message' => 'Errore del server'];
    }
    
    mysqli_stmt_bind_param($stmt, "s", $email);
    
    if (!mysqli_stmt_execute($stmt)) {
        error_log("loginUser - mysqli_stmt_execute failed: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return ['status' => false, 'message' => 'Errore del server'];
    }
    
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 0) {
        mysqli_stmt_close($stmt);
        return ['status' => false, 'message' => 'Email o password non validi'];
    }
    
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (password_verify($password, $user['password'])) {
        // Password corretta, inizializza la sessione
        $_SESSION['email'] = $user['email'];
        $_SESSION['password'] = $user['password']; // Memorizza l'hash della password
        
        $redirect = $user['amministratore'] == 1 ? 'admin.php' : 'profile.php';
        
        return [
            'status' => true,
            'message' => 'Login effettuato con successo',
            'redirect' => $redirect,
            'isAdmin' => $user['amministratore'] == 1
        ];
    } else {
        return ['status' => false, 'message' => 'Email o password non validi'];
    }
}

/**
 * Aggiorna i dati dell'utente
 * @param mysqli $conn Connessione al database
 * @param string $email Email dell'utente (non modificabile)
 * @param array $data Dati da aggiornare (nome, cognome, ecc.)
 * @return array Risultato dell'operazione con status e messaggio
 */
function updateUserData($conn, $email, $data) {
    $query = "UPDATE utente SET ";
    $params = [];
    $types = "";
    
    foreach ($data as $key => $value) {
        if (in_array($key, ['nome', 'cognome', 'username'])) {
            $query .= "$key = ?, ";
            $params[] = sanitizeInput($value);
            $types .= "s";
        }
    }
    
    $query = rtrim($query, ", ");
    $query .= " WHERE email = ?";
    $params[] = $email;
    $types .= "s";
    
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        error_log("updateUserData - mysqli_prepare failed: " . mysqli_error($conn));
        return ['status' => false, 'message' => 'Errore nella preparazione della query'];
    }
    
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    
    if (!mysqli_stmt_execute($stmt)) {
        error_log("updateUserData - mysqli_stmt_execute failed: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return ['status' => false, 'message' => 'Errore nell\'aggiornamento dei dati'];
    }
    
    mysqli_stmt_close($stmt);
    return ['status' => true, 'message' => 'Dati aggiornati con successo'];
}

/**
 * Aggiorna la password dell'utente
 * @param mysqli $conn Connessione al database
 * @param string $email Email dell'utente
 * @param string $currentPassword Password attuale
 * @param string $newPassword Nuova password
 * @return array Risultato dell'operazione con status e messaggio
 */
function updateUserPassword($conn, $email, $currentPassword, $newPassword) {
    $userData = getUserData($conn, $email);
    
    if (!$userData) {
        return ['status' => false, 'message' => 'Utente non trovato'];
    }
    
    if (!password_verify($currentPassword, $userData['password'])) {
        return ['status' => false, 'message' => 'Password attuale non corretta'];
    }
    
    // Controlla la lunghezza della nuova password
    if (strlen($newPassword) < 8) {
        return ['status' => false, 'message' => 'La nuova password deve contenere almeno 8 caratteri'];
    }
    
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $query = "UPDATE utente SET password = ? WHERE email = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        error_log("updateUserPassword - mysqli_prepare failed: " . mysqli_error($conn));
        return ['status' => false, 'message' => 'Errore nella preparazione della query'];
    }
    
    mysqli_stmt_bind_param($stmt, "ss", $hashedPassword, $email);
    
    if (!mysqli_stmt_execute($stmt)) {
        error_log("updateUserPassword - mysqli_stmt_execute failed: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return ['status' => false, 'message' => 'Errore nell\'aggiornamento della password'];
    }
    
    mysqli_stmt_close($stmt);
    
    // Aggiorna la sessione con la nuova password
    $_SESSION['password'] = $hashedPassword;
    
    return ['status' => true, 'message' => 'Password aggiornata con successo'];
}
