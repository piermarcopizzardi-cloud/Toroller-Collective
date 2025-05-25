<?php
/**
 * Funzioni per la gestione del database e delle query comuni
 */

/**
 * Ottiene tutti i servizi dal database
 * @param mysqli $conn Connessione al database
 * @param array $filters Array con filtri da applicare (opzionale)
 * @return array|null Array di servizi o null in caso di errore
 */
function getAllServices($conn, $filters = []) {
    $query = "SELECT id, nome, categoria, descrizione FROM servizi";
    $whereConditions = [];
    $params = [];
    $types = "";
    
    // Aggiunta di filtri per la ricerca
    if (!empty($filters['search_term'])) {
        $whereConditions[] = "(nome LIKE ? OR descrizione LIKE ?)";
        $searchTerm = "%" . $filters['search_term'] . "%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "ss";
    }
    
    // Aggiunta di filtri per categoria
    if (!empty($filters['category']) && $filters['category'] !== 'all') {
        $whereConditions[] = "categoria = ?";
        $params[] = $filters['category'];
        $types .= "s";
    }
    
    // Costruzione della clausola WHERE se ci sono condizioni
    if (!empty($whereConditions)) {
        $query .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    $query .= " ORDER BY nome ASC";
    
    // Esecuzione della query con o senza parametri
    if (empty($params)) {
        $result = mysqli_query($conn, $query);
        if (!$result) {
            error_log("getAllServices - mysqli_query failed: " . mysqli_error($conn));
            return null;
        }
    } else {
        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            error_log("getAllServices - mysqli_prepare failed: " . mysqli_error($conn));
            return null;
        }
        
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        
        if (!mysqli_stmt_execute($stmt)) {
            error_log("getAllServices - mysqli_stmt_execute failed: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return null;
        }
        
        $result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
    }
    
    $services = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $services[] = $row;
    }
    
    return $services;
}

/**
 * Ottiene un servizio specifico dal database
 * @param mysqli $conn Connessione al database
 * @param int $serviceId ID del servizio da recuperare
 * @return array|null Dati del servizio o null se non trovato
 */
function getServiceById($conn, $serviceId) {
    $query = "SELECT * FROM servizi WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        error_log("getServiceById - mysqli_prepare failed: " . mysqli_error($conn));
        return null;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $serviceId);
    
    if (!mysqli_stmt_execute($stmt)) {
        error_log("getServiceById - mysqli_stmt_execute failed: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return null;
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $service = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    return $service;
}

/**
 * Ottiene tutte le categorie di servizi disponibili
 * @param mysqli $conn Connessione al database
 * @return array|null Lista di categorie o null in caso di errore
 */
function getAllServiceCategories($conn) {
    $query = "SELECT DISTINCT categoria FROM servizi ORDER BY categoria ASC";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        error_log("getAllServiceCategories - mysqli_query failed: " . mysqli_error($conn));
        return null;
    }
    
    $categories = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row['categoria'];
    }
    
    return $categories;
}

/**
 * Ottiene tutti gli utenti (per amministratori)
 * @param mysqli $conn Connessione al database
 * @return array|null Lista degli utenti o null in caso di errore
 */
function getAllUsers($conn) {
    $query = "SELECT id, nome, cognome, email, username, amministratore FROM utente ORDER BY nome, cognome";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        error_log("getAllUsers - mysqli_query failed: " . mysqli_error($conn));
        return null;
    }
    
    $users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    
    return $users;
}

/**
 * Elimina un utente dal database
 * @param mysqli $conn Connessione al database
 * @param string $email Email dell'utente da eliminare
 * @return bool True se l'operazione è riuscita, False altrimenti
 */
function deleteUser($conn, $email) {
    mysqli_begin_transaction($conn);
    
    try {
        $query = "DELETE FROM utente WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        
        if (!$stmt) {
            throw new Exception("deleteUser - mysqli_prepare failed: " . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, "s", $email);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("deleteUser - mysqli_stmt_execute failed: " . mysqli_stmt_error($stmt));
        }
        
        $affectedRows = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        
        if ($affectedRows <= 0) {
            throw new Exception("No user deleted");
        }
        
        mysqli_commit($conn);
        return true;
    } catch (Exception $e) {
        error_log("deleteUser - Transaction failed: " . $e->getMessage());
        mysqli_rollback($conn);
        return false;
    }
}

/**
 * Aggiunge un nuovo servizio al database
 * @param mysqli $conn Connessione al database
 * @param array $serviceData Dati del servizio (nome, descrizione, categoria)
 * @return int|bool ID del nuovo servizio o False in caso di errore
 */
function addService($conn, $serviceData) {
    $query = "INSERT INTO servizi (nome, descrizione, categoria) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        error_log("addService - mysqli_prepare failed: " . mysqli_error($conn));
        return false;
    }
    
    $nome = sanitizeInput($serviceData['nome']);
    $descrizione = sanitizeInput($serviceData['descrizione']);
    $categoria = sanitizeInput($serviceData['categoria']);
    
    mysqli_stmt_bind_param($stmt, "sss", $nome, $descrizione, $categoria);
    
    if (!mysqli_stmt_execute($stmt)) {
        error_log("addService - mysqli_stmt_execute failed: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return false;
    }
    
    $serviceId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    
    return $serviceId;
}

/**
 * Elimina un servizio dal database
 * @param mysqli $conn Connessione al database
 * @param int $serviceId ID del servizio da eliminare
 * @return bool True se l'operazione è riuscita, False altrimenti
 */
function deleteService($conn, $serviceId) {
    $query = "DELETE FROM servizi WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        error_log("deleteService - mysqli_prepare failed: " . mysqli_error($conn));
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $serviceId);
    
    if (!mysqli_stmt_execute($stmt)) {
        error_log("deleteService - mysqli_stmt_execute failed: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return false;
    }
    
    $affectedRows = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    
    return $affectedRows > 0;
}
