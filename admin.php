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
$error = ""; // Initialize error message
$success = ""; // Initialize success message

if (isset($_GET['success_msg'])) {
    $success = htmlspecialchars($_GET['success_msg']);
}

$is_editing_event = false;
$event_data_for_editing = [];

try {
    $conn = connetti("toroller");
    if (!$conn) {
        throw new Exception("Errore di connessione al database");
    }
} catch (Exception $e) {
    error_log("Errore database: " . $e->getMessage());
    $error = "Errore di connessione al database.";
}

// Logica per caricare i dati dell'evento se siamo in modalità modifica
if (isset($_GET['edit_event_id']) && $conn) {
    // Evita di ricaricare i dati per la modifica se è appena stato inviato un form di update (magari con errori)
    // Lascia che il form mostri i valori POST-ati in quel caso.
    // Questa condizione si attiva solo quando si clicca sul link "Modifica".
    if ($_SERVER["REQUEST_METHOD"] == "GET") { 
        $edit_event_id = intval($_GET['edit_event_id']);
        $stmt_edit = mysqli_prepare($conn, "SELECT * FROM eventi WHERE id = ?");
        mysqli_stmt_bind_param($stmt_edit, "i", $edit_event_id);
        mysqli_stmt_execute($stmt_edit);
        $result_edit = mysqli_stmt_get_result($stmt_edit);
        if ($event_row = mysqli_fetch_assoc($result_edit)) {
            $event_data_for_editing = $event_row;
            $is_editing_event = true;
        } else {
            $error = "Evento da modificare non trovato.";
        }
        mysqli_stmt_close($stmt_edit);
    }
}


// Gestione Aggiunta Evento (PHP-based)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_event']) && $conn) {
    $titolo = mysqli_real_escape_string($conn, $_POST['titolo']);
    $data = $_POST['data'];
    $luogo = mysqli_real_escape_string($conn, $_POST['luogo']);
    $descrizione = mysqli_real_escape_string($conn, $_POST['descrizione']);
    $immagine_nome = null;

    if (isset($_FILES['immagine']) && $_FILES['immagine']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'assets/events/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $file_extension = strtolower(pathinfo($_FILES['immagine']['name'], PATHINFO_EXTENSION));

        if (in_array($file_extension, $allowed_extensions)) {
            $immagine_nome = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $immagine_nome;
            if (!move_uploaded_file($_FILES['immagine']['tmp_name'], $upload_path)) {
                $error = "Errore nel caricamento dell'immagine.";
                $immagine_nome = null; // Reset on failure
            }
        } else {
            $error = "Tipo di file non supportato. Sono ammessi solo JPG, JPEG, PNG, GIF.";
        }
    } elseif (isset($_FILES['immagine']) && $_FILES['immagine']['error'] !== UPLOAD_ERR_NO_FILE) {
        $error = "Errore nel caricamento dell'immagine: cod. " . $_FILES['immagine']['error'];
    }

    if (empty($error)) { // Proceed only if no upload error
        $query = "INSERT INTO eventi (titolo, data, descrizione, luogo, immagine) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssss", $titolo, $data, $descrizione, $luogo, $immagine_nome);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = "Evento aggiunto con successo!";
        } else {
            $error = "Errore durante l'aggiunta dell'evento: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}

// Gestione Modifica Evento (PHP-based)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_event']) && $conn) {
    $event_id = intval($_POST['event_id']);
    $titolo = mysqli_real_escape_string($conn, $_POST['titolo']);
    $data = $_POST['data']; // Assicurati che il formato sia YYYY-MM-DD
    $luogo = mysqli_real_escape_string($conn, $_POST['luogo']);
    $descrizione = mysqli_real_escape_string($conn, $_POST['descrizione']);
    
    // Recupera il nome dell'immagine attuale dal DB
    $stmt_curr_img = mysqli_prepare($conn, "SELECT immagine FROM eventi WHERE id = ?");
    mysqli_stmt_bind_param($stmt_curr_img, "i", $event_id);
    mysqli_stmt_execute($stmt_curr_img);
    $result_curr_img = mysqli_stmt_get_result($stmt_curr_img);
    $immagine_nome_attuale = null;
    if ($row_img = mysqli_fetch_assoc($result_curr_img)) {
        $immagine_nome_attuale = $row_img['immagine'];
    }
    mysqli_stmt_close($stmt_curr_img);

    $immagine_per_db = $immagine_nome_attuale; // Inizializza con l'immagine attuale

    // Gestione upload nuova immagine
    if (isset($_FILES['immagine']) && $_FILES['immagine']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'assets/events/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true); // Assicura che la directory esista
        }
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $file_info = pathinfo($_FILES['immagine']['name']);
        $file_extension = strtolower($file_info['extension']);

        if (in_array($file_extension, $allowed_extensions)) {
            $nuova_immagine_nome_temporaneo = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $nuova_immagine_nome_temporaneo;

            if (move_uploaded_file($_FILES['immagine']['tmp_name'], $upload_path)) {
                // Nuova immagine caricata con successo
                // Cancella la vecchia immagine dal server, se esisteva ed era diversa
                if (!empty($immagine_nome_attuale) && $immagine_nome_attuale !== $nuova_immagine_nome_temporaneo) {
                    $percorso_vecchia_immagine = $upload_dir . $immagine_nome_attuale;
                    if (file_exists($percorso_vecchia_immagine)) {
                        unlink($percorso_vecchia_immagine);
                    }
                }
                $immagine_per_db = $nuova_immagine_nome_temporaneo; // Aggiorna nome file per DB
            } else {
                $error = "Errore nel salvataggio della nuova immagine.";
                // Non cambiare $immagine_per_db, mantiene quella attuale in caso di fallimento del move
            }
        } else {
            $error = "Tipo di file non supportato per la nuova immagine. Sono ammessi solo JPG, JPEG, PNG, GIF.";
        }
    } elseif (isset($_FILES['immagine']) && $_FILES['immagine']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Errore diverso da "nessun file", es. file troppo grande per php.ini
        $error = "Errore nel caricamento della nuova immagine: cod. " . $_FILES['immagine']['error'];
    }

    // Procedi con l'aggiornamento del database solo se non ci sono stati errori critici che vogliamo bloccare
    // L'errore di upload (se presente) verrà mostrato, ma gli altri campi potrebbero essere aggiornati.
    // Se $error è stato settato da un problema di upload, $immagine_per_db sarà ancora quella vecchia.
    // Se si vuole bloccare l'update anche dei campi testo in caso di errore upload, aggiungere qui: if(empty($error))
    
    $query_update = "UPDATE eventi SET titolo = ?, data = ?, descrizione = ?, luogo = ?, immagine = ? WHERE id = ?";
    $stmt_update = mysqli_prepare($conn, $query_update);
    mysqli_stmt_bind_param($stmt_update, "sssssi", $titolo, $data, $descrizione, $luogo, $immagine_per_db, $event_id);
    
    if (mysqli_stmt_execute($stmt_update)) {
        // Se c'era un errore (es. tipo file non valido) ma l'update SQL va a buon fine con la vecchia immagine,
        // $success sovrascriverebbe $error. Diamo priorità all'errore se presente.
        if (empty($error)) {
            $success = "Evento modificato con successo!";
        }
        // Redirect per pulire lo stato e i dati POST, e mostrare il messaggio di successo/errore
        $redirect_url = "admin.php#eventsSection";
        if (!empty($success)) $redirect_url .= "?success_msg=" . urlencode($success);
        // Se $error è stato settato (es. per l'immagine) ma l'update testuale è andato a buon fine,
        // potremmo voler ricaricare la pagina di modifica con l'errore visibile.
        // Per ora, un redirect semplice. Se l'errore immagine è critico, l'utente vedrà il messaggio.
        // Se l'update SQL fallisce, $error sarà settato sotto.
        
        // Per evitare di perdere $error se l'update SQL ha successo ma c'era un problema con l'immagine:
        if (!empty($error)) { // Se c'era un errore (es. upload) ma l'SQL è andato bene
             // Ricarica la pagina di modifica per mostrare l'errore e i dati (non reindirizzare subito)
             // Per fare questo, dovremmo ripopolare $event_data_for_editing con i dati POST e settare $is_editing_event
             $_GET['edit_event_id'] = $event_id; // Simula di essere ancora in modifica
             $is_editing_event = true;
             // Ripopola $event_data_for_editing con i dati inviati, così il form li mostra
             $event_data_for_editing = $_POST;
             $event_data_for_editing['immagine'] = $immagine_per_db; // usa l'immagine che sarebbe andata nel DB
        } else {
            header("Location: " . $redirect_url);
            exit();
        }

    } else {
        $error = "Errore durante la modifica dell'evento: " . mysqli_error($conn);
        // Se l'update fallisce, rimani sulla pagina di modifica con i dati inseriti e l'errore
        $_GET['edit_event_id'] = $event_id;
        $is_editing_event = true;
        $event_data_for_editing = $_POST; // Ripopola con i dati tentati
        $event_data_for_editing['immagine'] = $immagine_nome_attuale; // Ripristina l'immagine originale in caso di fallimento SQL
    }
    mysqli_stmt_close($stmt_update);
}


// Gestione Eliminazione Evento (PHP-based)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_event']) && isset($_POST['event_id']) && $conn) {
    $event_id = intval($_POST['event_id']);
    
    // Prima, recupera il nome dell'immagine per poterla cancellare
    $img_query = "SELECT immagine FROM eventi WHERE id = ?";
    $stmt_img = mysqli_prepare($conn, $img_query);
    mysqli_stmt_bind_param($stmt_img, "i", $event_id);
    mysqli_stmt_execute($stmt_img);
    $result_img = mysqli_stmt_get_result($stmt_img);
    $immagine_da_cancellare = null;
    if ($row = mysqli_fetch_assoc($result_img)) {
        $immagine_da_cancellare = $row['immagine'];
    }
    mysqli_stmt_close($stmt_img);

    // Poi, elimina l'evento dal database
    $query = "DELETE FROM eventi WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $event_id);
    
    if (mysqli_stmt_execute($stmt)) {
        if ($immagine_da_cancellare) {
            $percorso_immagine = 'assets/events/' . $immagine_da_cancellare;
            if (file_exists($percorso_immagine)) {
                unlink($percorso_immagine);
            }
        }
        $success = "Evento eliminato con successo!";
    } else {
        $error = "Errore durante l'eliminazione dell'evento: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
}


// Gestione del profilo e cambio password
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile'])) {
        $nome = mysqli_real_escape_string($conn, $_POST['nome']);
        $cognome = mysqli_real_escape_string($conn, $_POST['cognome']);
        $dataNascita = mysqli_real_escape_string($conn, $_POST['data_nascita']);
        
        $updateQuery = "UPDATE utente SET nome = '$nome', cognome = '$cognome', data_nascita = '$dataNascita' WHERE email = '$email'";
        if (mysqli_query($conn, $updateQuery)) {
            $success = "Profilo aggiornato con successo!";
            // Refresh user data
            $result = mysqli_query($conn, $query);
            $user = mysqli_fetch_assoc($result);
        } else {
            $error = "Errore durante l'aggiornamento del profilo.";
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $currentPassword = mysqli_real_escape_string($conn, $_POST['current_password']);
        $newPassword = mysqli_real_escape_string($conn, $_POST['new_password']);
        $confirmPassword = mysqli_real_escape_string($conn, $_POST['confirm_password']);
        
        if (password_verify($currentPassword, $user['password'])) {
            if ($newPassword === $confirmPassword) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateQuery = "UPDATE utente SET password = '$hashedPassword' WHERE email = '$email'";
                
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
    <link rel="stylesheet" href="<?php echo $basePath; ?>/style/cart.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>/style/admin.css">
</head>
<body class="admin-page">
    <?php include 'components/header.php'?>

    <div class="profile-container">
        <h1 class="profile-title">Pannello di Amministrazione</h1>

        <div id="messagesGlobal"></div> <!-- Per messaggi globali di successo/errore -->

        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Tab Navigation -->
        <div class="admin-tabs">
            <div class="admin-tab active" data-tab="profile">Profilo</div>
            <div class="admin-tab" data-tab="products">Gestione Prodotti</div>
            <div class="admin-tab" data-tab="users">Gestione Utenti</div>
            <div class="admin-tab" data-tab="events">Gestione Eventi</div>
        </div>

        <!-- Profile Section -->
        <div class="admin-section active" id="profileSection">
            <div class="profile-section">
                <h2>Informazioni Personali</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">Nome</label>
                        <input type="text" name="nome" class="form-input" value="<?php echo htmlspecialchars($user['nome']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Cognome</label>
                        <input type="text" name="cognome" class="form-input" value="<?php echo htmlspecialchars($user['cognome']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-input" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Data di Nascita</label>
                        <input type="date" name="data_nascita" class="form-input" value="<?php echo htmlspecialchars($user['data_nascita']); ?>" required>
                    </div>

                    <button type="submit" name="update_profile" class="submit-btn">Aggiorna Profilo</button>
                </form>
            </div>

            <div class="profile-section">
                <h2>Cambia Password</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">Password Attuale</label>
                        <input type="password" name="current_password" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nuova Password</label>
                        <input type="password" name="new_password" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Conferma Nuova Password</label>
                        <input type="password" name="confirm_password" class="form-input" required>
                    </div>

                    <button type="submit" name="change_password" class="submit-btn">Cambia Password</button>
                </form>
            </div>
        </div>
        <!-- Products Section -->
        <div class="admin-section" id="productsSection">
            <h2>Gestione Prodotti</h2>
            
            <!-- Add Product Form -->
            <div class="form-section">
                <h3>Aggiungi Nuovo Prodotto</h3>
                <div id="messagesProduct"></div>
                <form id="productForm" class="needs-validation" enctype="multipart/form-data" novalidate>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Tipologia</label>
                            <input type="text" name="tipologia" class="form-input" required>
                            <div class="invalid-feedback">
                                La tipologia è obbligatoria
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Prezzo</label>
                            <input type="number" step="0.01" name="prezzo" class="form-input" required>
                            <div class="invalid-feedback">
                                Il prezzo è obbligatorio e deve essere un numero.
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Quantità</label>
                            <input type="number" name="quantita" class="form-input" required>
                            <div class="invalid-feedback">
                                La quantità è obbligatoria e deve essere un numero intero.
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Colore</label>
                            <input type="text" name="colore" class="form-input" required>
                            <div class="invalid-feedback">
                                Il colore è obbligatorio.
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Descrizione</label>
                        <textarea name="descrizione" class="form-input" rows="3" required></textarea>
                        <div class="invalid-feedback">
                            La descrizione è obbligatoria.
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Immagine</label>
                        <input type="file" name="immagine" class="form-input" accept="image/*" required>
                        <div class="invalid-feedback">
                            L'immagine è obbligatoria.
                        </div>
                    </div>
                    <button type="submit" class="submit-btn">Aggiungi Prodotto</button>
                </form>
            </div>

            <!-- Products Table -->
            <div class="table-section">
                <h3>Elenco Prodotti</h3>
                <table id="productsTable" class="admin-table">
                    <thead>
                        <tr>
                            <th>Tipologia</th>
                            <th>Prezzo</th>
                            <th>Quantità</th>
                            <th>Colore</th>
                            <th>Immagine</th>
                            <th>Descrizione</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $conn_products = connetti("toroller");
                        $products_query = "SELECT * FROM prodotti ORDER BY id DESC";
                        $products_result = mysqli_query($conn_products, $products_query);
                        if ($products_result && mysqli_num_rows($products_result) > 0) {
                            while ($product = mysqli_fetch_assoc($products_result)) {
                                echo "<tr id='product-" . $product['id'] . "'>";
                                echo "<td>" . htmlspecialchars($product['tipologia']) . "</td>";
                                echo "<td>" . htmlspecialchars($product['prezzo']) . "</td>";
                                echo "<td>" . htmlspecialchars($product['quantita']) . "</td>";
                                echo "<td>" . htmlspecialchars($product['colore']) . "</td>";
                                echo "<td><img src='assets/products/" . htmlspecialchars($product['immagine']) . "' height='50'></td>";
                                echo "<td>" . htmlspecialchars($product['descrizione']) . "</td>";
                                echo "<td><button class='delete-btn' onclick='deleteProduct(" . $product['id'] . ")'>Elimina</button></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7'>Nessun prodotto trovato.</td></tr>";
                        }
                        mysqli_close($conn_products);
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Users Section -->
        <div class="admin-section" id="usersSection">
            <h2>Gestione Utenti</h2>
            <div class="table-section">
                <h3>Elenco Utenti</h3>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Cognome</th>
                            <th>Email</th>
                            <th>Data di Nascita</th>
                            <th>Amministratore</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $conn_users = connetti("toroller");
                        $users_query = "SELECT * FROM utente ORDER BY nome ASC";
                        $users_result = mysqli_query($conn_users, $users_query);
                        if ($users_result && mysqli_num_rows($users_result) > 0) {
                            while ($row = mysqli_fetch_assoc($users_result)) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['nome']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['cognome']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['data_nascita']) . "</td>";
                                echo "<td>" . ($row['amministratore'] ? 'Sì' : 'No') . "</td>";
                                echo "<td>";
                                if ($row['email'] !== $_SESSION['email']) { // Non permettere l'eliminazione del proprio account
                                    echo "<form method='POST' action='' style='display:inline-block;'>";
                                    echo "<input type='hidden' name='user_email' value='" . htmlspecialchars($row['email']) . "'>";
                                    echo "<button type='submit' name='delete_user' class='delete-btn' onclick='return confirm(\"Sei sicuro di voler eliminare questo utente?\")'>Elimina</button>";
                                    echo "</form>";
                                }
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>Nessun utente trovato.</td></tr>";
                        }
                        mysqli_close($conn_users);
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Events Section -->
        <div class="admin-section" id="eventsSection">
            <h2>Gestione Eventi</h2>
            <div class="form-section">
                <h3><?php echo $is_editing_event ? 'Modifica Evento' : 'Aggiungi Nuovo Evento'; ?></h3>
                <div id="messagesEvent">
                    <?php if (!empty($error) && $_SERVER["REQUEST_METHOD"] != "GET"): // Mostra errore solo se non è un GET iniziale per edit ?>
                        <div class="error-message"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if (!empty($success) && $_SERVER["REQUEST_METHOD"] != "GET"): // Mostra successo solo se non è un GET iniziale ?>
                        <div class="success-message"><?php echo $success; ?></div>
                    <?php endif; ?>
                </div>
                <form id="eventForm" method="POST" action="admin.php#eventsSection" enctype="multipart/form-data">
                    <?php if ($is_editing_event): ?>
                        <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($event_data_for_editing['id'] ?? ''); ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="form-label">Titolo</label>
                        <input type="text" name="titolo" class="form-input" required value="<?php echo htmlspecialchars($is_editing_event ? ($event_data_for_editing['titolo'] ?? '') : ($_POST['titolo'] ?? '')); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Data</label>
                        <input type="date" name="data" class="form-input" required value="<?php echo htmlspecialchars($is_editing_event ? ($event_data_for_editing['data'] ?? '') : ($_POST['data'] ?? '')); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Luogo</label>
                        <input type="text" name="luogo" class="form-input" required value="<?php echo htmlspecialchars($is_editing_event ? ($event_data_for_editing['luogo'] ?? '') : ($_POST['luogo'] ?? '')); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Descrizione</label>
                        <textarea name="descrizione" class="form-input" rows="3" required><?php echo htmlspecialchars($is_editing_event ? ($event_data_for_editing['descrizione'] ?? '') : ($_POST['descrizione'] ?? '')); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Immagine <?php echo $is_editing_event ? '(opzionale, per sostituire)' : '(opzionale)'; ?></label>
                        <?php if ($is_editing_event && !empty($event_data_for_editing['immagine'])): ?>
                            <p style="margin-bottom: 5px;">Immagine attuale: <br><img src="assets/events/<?php echo htmlspecialchars($event_data_for_editing['immagine']); ?>" height="70" alt="Immagine attuale" style="margin-top:5px; border-radius:4px;"></p>
                            <p><small>Scegli un nuovo file solo se vuoi sostituire l'immagine attuale.</small></p>
                        <?php endif; ?>
                        <input type="file" name="immagine" class="form-input" accept="image/*">
                    </div>
                    
                    <?php if ($is_editing_event): ?>
                        <button type="submit" name="update_event" class="submit-btn">Modifica Evento</button>
                        <a href="admin.php#eventsSection" class="submit-btn" style="background-color: #6c757d; margin-left: 10px; text-decoration: none;">Annulla</a>
                    <?php else: ?>
                        <button type="submit" name="add_event" class="submit-btn">Aggiungi Evento</button>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-section">
                <h3>Elenco Eventi</h3>
                <table id="eventsTable" class="admin-table">
                    <thead>
                        <tr>
                            <th>Titolo</th>
                            <th>Data</th>
                            <th>Luogo</th>
                            <th>Descrizione</th>
                            <th>Immagine</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($conn) { // Assicurati che $conn sia valido prima di usarlo
                            $events_query = "SELECT * FROM eventi ORDER BY data DESC";
                            $events_result = mysqli_query($conn, $events_query);
                            if ($events_result && mysqli_num_rows($events_result) > 0) {
                                while ($event = mysqli_fetch_assoc($events_result)) {
                                    echo "<tr id='event-" . $event['id'] . "'>";
                                    echo "<td>" . htmlspecialchars($event['titolo']) . "</td>";
                                    echo "<td>" . htmlspecialchars(date('d/m/Y', strtotime($event['data']))) . "</td>";
                                    echo "<td>" . htmlspecialchars($event['luogo']) . "</td>";
                                    echo "<td>" . htmlspecialchars(substr($event['descrizione'], 0, 50)) . (strlen($event['descrizione']) > 50 ? '...' : '') . "</td>";
                                    echo "<td>";
                                    if (!empty($event['immagine'])) {
                                        echo "<img src='assets/events/" . htmlspecialchars($event['immagine']) . "' height='50' style='border-radius:4px;'>";
                                    } else {
                                        echo "N/A";
                                    }
                                    echo "</td>";
                                    echo "<td class='action-buttons'>";
                                    echo "<a href='admin.php?edit_event_id=" . $event['id'] . "#eventForm' class='edit-btn' style='text-decoration:none; color:white; display:inline-block; margin-right:5px; padding: 6px 12px; border-radius: 6px; font-size: 14px; font-weight: 500;'>Modifica</a>";
                                    echo "<form method='POST' action='admin.php#eventsSection' style='display:inline-block;'>";
                                    echo "<input type='hidden' name='event_id' value='" . $event['id'] . "'>";
                                    echo "<button type='submit' name='delete_event' class='delete-btn' onclick='return confirm(\"Sei sicuro di voler eliminare questo evento?\")'>Elimina</button>";
                                    echo "</form>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6'>Nessun evento trovato.</td></tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>Errore di connessione al database.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.admin-tab');
            const sections = document.querySelectorAll('.admin-section');

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    tabs.forEach(t => t.classList.remove('active'));
                    sections.forEach(s => s.classList.remove('active'));

                    tab.classList.add('active');
                    document.getElementById(tab.dataset.tab + 'Section').classList.add('active');
                });
            });

            // Gestione Prodotti (come da codice precedente)
            const productForm = document.querySelector('#productForm');
            if (productForm) {
                productForm.addEventListener('submit', addProduct);
            }

            // Rimuoviamo le funzioni JS per la gestione eventi, ora è PHP-based
            /*
            const eventForm = document.querySelector('#eventForm');
            if (eventForm) {
                // eventForm.addEventListener('submit', addEvent); // Rimosso
            }
            */
        });

        function showMessage(containerId, message, isError = false) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert ${isError ? 'alert-danger' : 'alert-success'}`;
            alertDiv.textContent = message;
            const container = document.getElementById(containerId);
            if (container) { // Controlla se il container esiste
                container.innerHTML = ''; // Pulisce messaggi precedenti
                container.appendChild(alertDiv);

                setTimeout(() => {
                    alertDiv.remove();
                }, 5000);
            } else {
                // Fallback o log se il container non è trovato, per i messaggi JS
                // Per i messaggi PHP, sono già renderizzati nel DOM.
                console.warn("Contenitore messaggi JS non trovato:", containerId);
            }
        }

        function addProduct(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            formData.append('action', 'add_product');

            fetch('admin_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('messagesProduct', data.message);
                    const productsTable = document.querySelector('#productsTable tbody');
                    const noProductRow = productsTable.querySelector('td[colspan="7"]');
                    if (noProductRow) noProductRow.parentElement.remove();

                    const newRow = document.createElement('tr');
                    newRow.id = `product-${data.product.id}`;
                    newRow.innerHTML = `
                        <td>${data.product.tipologia}</td>
                        <td>${data.product.prezzo}</td>
                        <td>${data.product.quantita}</td>
                        <td>${data.product.colore}</td>
                        <td><img src="assets/products/${data.product.immagine}" height="50"></td>
                        <td>${data.product.descrizione}</td>
                        <td><button class="delete-btn" onclick="deleteProduct(${data.product.id})">Elimina</button></td>
                    `;
                    productsTable.insertBefore(newRow, productsTable.firstChild);
                    form.reset();
                } else {
                    showMessage('messagesProduct', data.error, true);
                }
            })
            .catch(error => {
                showMessage('messagesProduct', 'Si è verificato un errore durante l\'aggiunta del prodotto', true);
                console.error('Error:', error);
            });
        }

        function deleteProduct(id) {
            if (!confirm('Sei sicuro di voler eliminare questo prodotto?')) return;

            const formData = new FormData();
            formData.append('action', 'delete_product');
            formData.append('id', id);

            fetch('admin_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('messagesGlobal', data.message); 
                    document.querySelector(`#product-${id}`).remove();
                } else {
                    showMessage('messagesGlobal', data.error, true);
                }
            })
            .catch(error => {
                showMessage('messagesGlobal', 'Si è verificato un errore durante l\'eliminazione del prodotto', true);
                console.error('Error:', error);
            });
        }

        // Le funzioni addEvent e deleteEvent sono state rimosse/commentate
        // perché la gestione eventi è ora PHP-based.
        /*
        function addEvent(event) {
            // ... Logica JS precedente rimossa ...
        }

        function deleteEvent(id) {
            // ... Logica JS precedente rimossa ...
        }
        */

    </script>
    <script src="<?php echo $basePath; ?>/components/header.js"></script>
</body>
</html>