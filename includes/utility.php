<?php
/**
 * Funzioni di utilità per tutte le pagine dell'applicazione
 */

/**
 * Pulisce e prepara i dati di input per l'inserimento
 * @param string $data Dati da pulire
 * @return string Dati puliti
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Verifica se una stringa è un'email valida
 * @param string $email Email da verificare
 * @return bool True se l'email è valida, false altrimenti
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Genera un messaggio di successo formattato
 * @param string $message Il messaggio da mostrare
 * @return string HTML formattato
 */
function successMessage($message) {
    return '<div class="alert alert-success">' . $message . '</div>';
}

/**
 * Genera un messaggio di errore formattato
 * @param string $message Il messaggio da mostrare
 * @return string HTML formattato
 */
function errorMessage($message) {
    return '<div class="alert alert-error">' . $message . '</div>';
}

/**
 * Reindirizza ad un'altra pagina con un messaggio
 * @param string $url URL di destinazione
 * @param string $message Messaggio da passare (opzionale)
 * @param string $type Tipo di messaggio ('error' o 'success')
 */
function redirectWithMessage($url, $message = '', $type = 'success') {
    if (!empty($message)) {
        $paramName = ($type === 'error') ? 'error' : 'success_msg';
        $url .= (strpos($url, '?') === false) ? '?' : '&';
        $url .= $paramName . '=' . urlencode($message);
    }
    header("Location: $url");
    exit();
}

/**
 * Controlla se l'utente è loggato
 * @return bool True se l'utente è loggato, false altrimenti
 */
function isUserLoggedIn() {
    return isset($_SESSION['email']) && isset($_SESSION['password']);
}

/**
 * Ottiene il percorso base per i link relativi
 * @return string Percorso base
 */
function getBasePath() {
    $basePath = dirname($_SERVER['PHP_SELF']);
    return ($basePath === '/') ? '' : $basePath;
}
