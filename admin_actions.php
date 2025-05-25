<?php
session_start();
include("conn.php");

// Verifica che l'utente sia amministratore
function checkAdmin($conn) {
    if (!isset($_SESSION['email'])) {
        http_response_code(401);
        die(json_encode(['error' => 'Non autorizzato']));
    }
    
    $email = $_SESSION['email'];
    $query = "SELECT amministratore FROM utente WHERE email = ?";
    $stmt = mysqli_prepare($conn, $query);

    if (!$stmt) {
        http_response_code(500);
        // Log the actual MySQL error for debugging
        error_log("admin_actions.php - checkAdmin - mysqli_prepare failed: " . mysqli_error($conn));
        die(json_encode(['error' => 'Errore interno del server (DB Prepare)']));
    }

    mysqli_stmt_bind_param($stmt, "s", $email);
    if (!mysqli_stmt_execute($stmt)) {
        http_response_code(500);
        error_log("admin_actions.php - checkAdmin - mysqli_stmt_execute failed: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        die(json_encode(['error' => 'Errore interno del server (DB Execute)']));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt); // Close statement after fetching
    
    if (!$user || !$user['amministratore']) {
        http_response_code(403);
        die(json_encode(['error' => 'Accesso negato']));
    }
}

// Inizializza la connessione
$conn = connetti("toroller_semplificato");
if (!$conn) {
    http_response_code(500);
    die(json_encode(['error' => 'Errore di connessione al database']));
}

// Verifica che l'utente sia admin
checkAdmin($conn);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            // Rimossi i case non utilizzati (add_product, delete_product, add_event, delete_event)
            // mantenendo solo la struttura di base per eventuali aggiornamenti futuri
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Azione non valida']);
                break;
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Azione non specificata']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Gestione delle richieste GET
    if (isset($_GET['action']) && $_GET['action'] === 'get_user_data') {
        // Verifica se l'utente è autenticato come amministratore
        if (!isset($_SESSION['email'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non autorizzato']);
            exit;
        }
        
        // Ottieni i dati dell'utente
        $email = $_SESSION['email'];
        $query = "SELECT nome, cognome, username, email FROM utente WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['error' => 'Errore interno del server']);
            exit;
        }
        
        mysqli_stmt_bind_param($stmt, "s", $email);
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            http_response_code(500);
            echo json_encode(['error' => 'Errore durante l\'esecuzione della query']);
            exit;
        }
        
        $result = mysqli_stmt_get_result($stmt);
        $user_data = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if ($user_data) {
            echo json_encode([
                'success' => true,
                'user' => $user_data
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Dati utente non trovati']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Azione GET non valida o non specificata']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Metodo non permesso']);
}

mysqli_close($conn);
