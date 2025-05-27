<?php
session_start();
include("conn.php");

// Controlla se l'utente è loggato
$isLoggedIn = isset($_SESSION['email']) && isset($_SESSION['password']);
$user = null; 

// Se l'utente ha cliccato su logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

try {
    $conn = connetti("toroller_semplificato"); 
    if (!$conn) {
        throw new Exception("Errore di connessione al database");
    }    // CARICAMENTO DATI UTENTE LOGGATO
    if ($isLoggedIn) {
        $admin_email = mysqli_real_escape_string($conn, $_SESSION['email']);
        $query_user = "SELECT * FROM utente WHERE email = '$admin_email'";
        $result_user = mysqli_query($conn, $query_user);
        if ($row_user = mysqli_fetch_assoc($result_user)) {
            $user = $row_user;
            // controllo admin
            if (!isset($user['amministratore']) || $user['amministratore'] != 1) {
                // Non è un admin, reindirizza o mostra errore
                session_destroy(); 
                header("Location: login.php?error=Accesso negato. Permessi insufficienti.");
                exit();
            }
        } else {
            session_destroy();
            header("Location: login.php?error=Sessione invalida o utente non trovato nel database corretto.");
            exit();
        }
    } else {
        header("Location: login.php?error=Accesso negato. Devi effettuare il login.");
        exit();
    }

} catch (Exception $e) {
    error_log("Errore database: " . $e->getMessage());
    $error = "Errore di connessione al database.";
}

// ELIMINA UTENTI
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_user']) && $conn) {
    $email = mysqli_real_escape_string($conn, $_POST['user_email']);
    // controllo per impedire eliminazione admin
    if ($email === $_SESSION['email']) {
        $error = "Non puoi eliminare il tuo account da questa interfaccia.";
    } else {
        // elimina email
        mysqli_query($conn, "DELETE FROM utente WHERE email='$email'");
        if (mysqli_affected_rows($conn) > 0) {
            $success = "Utente eliminato con successo!";
        } else {
            $error = "Utente non trovato o già eliminato.";
        }
    }
}

// AGGIORNA DATI PROFILO
if ($_SERVER["REQUEST_METHOD"] == "POST" && $user && $conn) {
    if (isset($_POST['update_profile'])) {
        $nome = mysqli_real_escape_string($conn, $_POST['nome']);
        $cognome = mysqli_real_escape_string($conn, $_POST['cognome']);
        mysqli_query($conn, "UPDATE utente SET nome='$nome', cognome='$cognome' WHERE email='{$user['email']}'");
        $success = "Profilo aggiornato con successo!";
    } // CAMBIO PASSWORD
    elseif (isset($_POST['change_password'])) {
        $current = $_POST['current_password'];
        $new    = $_POST['new_password'];
        $confirm= $_POST['confirm_password'];
        if (!password_verify($current, $user['password'])) {
            $error = "Password attuale non corretta.";
        } elseif ($new !== $confirm) {
            $error = "Le nuove password non corrispondono.";
        } elseif (strlen($new) < 8) {
            $error = "La nuova password deve contenere almeno 8 caratteri.";
        } else {
            // HASHING PWS + AGGIORNAMENTO DATABASE_PWS
            $hash = password_hash($new, PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE utente SET password='$hash' WHERE email='{$user['email']}'");
            $_SESSION['password'] = $hash;
            $success = "Password aggiornata con successo!";
        }
    }
}

// AGGIUNTA SERVIZIO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_service']) && $conn) {
    $nome_servizio = mysqli_real_escape_string($conn, $_POST['nome_servizio']);
    $descrizione_servizio = mysqli_real_escape_string($conn, $_POST['descrizione_servizio']);
    $categoria = mysqli_real_escape_string($conn, $_POST['categoria']);
    if (empty($nome_servizio) || empty($descrizione_servizio) || empty($categoria)) {
        $error = "Tutti i campi sono obbligatori.";
    } else {
        mysqli_query($conn, "INSERT INTO servizi (nome, descrizione, categoria) VALUES ('$nome_servizio', '$descrizione_servizio', '$categoria')");
        if (mysqli_affected_rows($conn) > 0) {
            $success = "Servizio aggiunto con successo!";
        } else {
            $error = "Errore durante l'aggiunta del servizio.";
        }
    }
}

// ELIMINA SERVIZIO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_service']) && $conn) {
    $id = intval($_POST['service_id']);
    if ($id <= 0) {
        $error = "ID servizio non valido.";
    } else {
        mysqli_query($conn, "DELETE FROM servizi WHERE id=$id");
        if (mysqli_affected_rows($conn) > 0) {
            $success = "Servizio eliminato con successo!";
        } else {
            $error = "Servizio non trovato o già eliminato.";
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
    <!-- unico modo per far caricare correttament eil front-end (js,css)-->
    <?php $basePath = dirname($_SERVER['PHP_SELF']); if ($basePath == '/') $basePath = ''; ?>
    <meta name="base-path" content="<?php echo rtrim(dirname($_SERVER['PHP_SELF']), '/'); ?>">
    <link rel="stylesheet" href="<?php echo $basePath; ?>/style/header.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>/style/admin.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>/style/footer.css">
</head>
<body class="admin-page">
    <?php include 'components/header.php'?>

    <div class="profile-container">
        <h1 class="profile-title">Pannello di Amministrazione</h1>

        <div id="messagesGlobal"></div> 

        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>        <!-- Tab Navigation -->            <div class="admin-tabs">
            <div class="admin-tab active" data-tab="profile">Profilo</div>
            <div class="admin-tab" data-tab="services">Gestione Servizi</div>
            <div class="admin-tab" data-tab="users">Gestione Utenti</div>
        </div>

        <!-- FORM MODIFICA PROFILO-->
        <div class="admin-section active" id="profileSection">
            <div class="profile-section">
                <h2>Informazioni Personali</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">Nome</label>
                        <input type="text" name="nome" class="form-input" value="<?php echo isset($user['nome']) ? ($user['nome']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Cognome</label>
                        <input type="text" name="cognome" class="form-input" value="<?php echo isset($user['cognome']) ? ($user['cognome']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-input" value="<?php echo isset($user['email']) ? ($user['email']) : ''; ?>" disabled>
                    </div>

                    <button type="submit" name="update_profile" class="submit-btn">Aggiorna Profilo</button>
                </form>
            </div>

        <!--FORM CAMBIO PWS-->
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

                    <button type="submit" name="change_password" class="submit-btn">Cambia Password</button>
                </form>
            </div>
        </div>


        <div class="admin-section" id="servicesSection">
            <h2>Gestione Servizi</h2>
            <!--   FORM AGGIUNGI SERVIZI -->
            <div class="form-section">
                <h3>Aggiungi Nuovo Servizio</h3>
                <div id="messagesService"></div> 
                <form method="POST" action="" class="needs-validation" novalidate>
                    <div class="form-group">
                        <label class="form-label" for="nome_servizio">Nome Servizio</label>
                        <input type="text" id="nome_servizio" name="nome_servizio" class="form-input" required>
                        <div class="invalid-feedback">
                            Il nome del servizio è obbligatorio.
                        </div>
                    </div>                    <div class="form-group">
                        <label class="form-label" for="descrizione_servizio">Descrizione</label>
                        <textarea id="descrizione_servizio" name="descrizione_servizio" class="form-input" rows="3" required></textarea>
                        <div class="invalid-feedback">
                            La descrizione è obbligatoria.
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="categoria">Categoria</label>
                        <input type="text" id="categoria" name="categoria" class="form-input" required>
                        <div class="invalid-feedback">
                            La categoria è obbligatoria.
                        </div>
                    </div>
                    <button type="submit" name="add_service" class="submit-btn">Aggiungi Servizio</button>
                </form>
            </div>

            
            <div class="table-section">
                <h3>Elenco Servizi</h3>
                <table id="servicesTable" class="admin-table"> 
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Categoria</th>
                            <th>Descrizione</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($conn) { 
                            $services_query = "SELECT * FROM servizi ORDER BY id DESC";
                            $services_result = mysqli_query($conn, $services_query);
                            if ($services_result && mysqli_num_rows($services_result) > 0) {
                                while ($service = mysqli_fetch_assoc($services_result)) {
                                    echo "<tr>";
                                    echo "<td>" . ($service['nome']) . "</td>";
                                    echo "<td>" . ($service['categoria']) . "</td>";
                                    echo "<td>" . ($service['descrizione']) . "</td>";
                                    echo "<td>";
                                    // BOTTONE PER ELIMINAZIONE SERVIZIO
                                    echo "<form method='POST' action='' style='display:inline-block;'>";
                                    echo "<input type='hidden' name='service_id' value='" . $service['id'] . "'>";
                                    echo "<button type='submit' name='delete_service' class='delete-btn' onclick='return confirm(\"Sei sicuro di voler eliminare questo servizio?\")'>Elimina</button>";
                                    echo "</form>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4'>Nessun servizio trovato.</td></tr>";
                            }
                          
                        } else {
                            echo "<tr><td colspan='4'>Errore di connessione al database.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>        
        </div>

        <!-- SEZIONE UTENTI -->
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
                            <th>Amministratore</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($conn) {
                            // caricamento in ordine alfabetico degli user
                            $users_query = "SELECT * FROM utente ORDER BY nome ASC";
                            $users_result = mysqli_query($conn, $users_query);
                            if ($users_result && mysqli_num_rows($users_result) > 0) {
                                
                                while ($row = mysqli_fetch_assoc($users_result)) {
                                    // caricamento dei singoli utenti
                                    echo "<tr>";
                                    echo "<td>" . ($row['nome']) . "</td>";
                                    echo "<td>" . ($row['cognome']) . "</td>";
                                    echo "<td>" . ($row['email']) . "</td>";
                                    echo "<td>" . ($row['amministratore'] ? 'Sì' : 'No') . "</td>";
                                    echo "<td>";
                                    // BOTTONE - ELIMINA UTENTE
                                    if ($row['email'] !== $_SESSION['email']) {
                                        echo "<form method='POST' action='' style='display:inline-block;'>";
                                        echo "<input type='hidden' name='user_email' value='" . ($row['email']) . "'>";
                                        echo "<button type='submit' name='delete_user' class='delete-btn' onclick='return confirm(\"Sei sicuro di voler eliminare questo utente?\")'>Elimina</button>";
                                        echo "</form>";
                                    }
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5'>Nessun utente trovato.</td></tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5'>Errore di connessione al database.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const tabs = document.querySelectorAll('.admin-tab');
        const sections = document.querySelectorAll('.admin-section');
        const messagesGlobal = document.getElementById('messagesGlobal'); // For global messages

        function clearMessages() {
            if (messagesGlobal) messagesGlobal.innerHTML = '';
            // Clear messages in other specific areas if needed
            // Example: document.getElementById('messagesProduct').innerHTML = '';
        }

        function showSection(targetSectionId) {
            clearMessages(); // Clear messages when changing tabs

            sections.forEach(section => {
                if (section.id === targetSectionId + 'Section') {
                    section.classList.add('active');
                } else {
                    section.classList.remove('active');
                }
            });

            tabs.forEach(t => {
                if (t.dataset.tab === targetSectionId) {
                    t.classList.add('active');
                } else {
                    t.classList.remove('active');
                }
            });
            // Update URL hash, but without 'Section' to keep it cleaner if preferred
            // Or keep 'Section' if that's how it was intended for direct linking
            window.location.hash = targetSectionId;
        }

        tabs.forEach(tab => {
            tab.addEventListener('click', function (event) {
                event.preventDefault(); // Prevent default anchor behavior if tabs were <a> tags
                const targetSectionId = this.dataset.tab;
                showSection(targetSectionId);
            });
        });

        // Activate tab based on URL hash on page load
        if (window.location.hash) {
            // Remove '#' and 'Section' (if it was added to the hash) to get the tab ID
            const hashTabId = window.location.hash.substring(1).replace('Section', '');
            const tabToActivate = document.querySelector(`.admin-tab[data-tab="${hashTabId}"]`);
            if (tabToActivate) {
                showSection(hashTabId);
            } else {
                // Default to profile if hash is invalid or no corresponding tab
                showSection('profile');
            }
        } else {
            // Default to the first tab (profile) if no hash
            showSection('profile');
        }
    });
    </script>
    <?php include 'components/footer.php'; ?>
</body>
</html>

