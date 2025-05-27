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

if (isset($_GET['success_msg'])) {
    $success = htmlspecialchars($_GET['success_msg']);
}

try {
    $conn = connetti("toroller_semplificato"); // Corrected DB name
    if (!$conn) {
        throw new Exception("Errore di connessione al database");
    }

    // Fetch user data if logged in
    if ($isLoggedIn) {
        $admin_email = $_SESSION['email'];
        $query_user = "SELECT * FROM utente WHERE email = ?";
        $stmt_user = mysqli_prepare($conn, $query_user);
        mysqli_stmt_bind_param($stmt_user, "s", $admin_email);
        mysqli_stmt_execute($stmt_user);
        $result_user = mysqli_stmt_get_result($stmt_user);
        if ($row_user = mysqli_fetch_assoc($result_user)) {
            $user = $row_user;
            // Verifica se l'utente è effettivamente un amministratore
            if (!isset($user['amministratore']) || $user['amministratore'] != 1) {
                // Non è un admin, reindirizza o mostra errore
                session_destroy(); // Opzionale: utile per forzare un nuovo login
                header("Location: login.php?error=Accesso negato. Permessi insufficienti.");
                exit();
            }
        } else {
            // User not found in DB with the session email
            session_destroy();
            header("Location: login.php?error=Sessione invalida o utente non trovato nel database corretto.");
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

// Gestione Eliminazione Utente semplificata
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_user']) && $conn) {
    $email = mysqli_real_escape_string($conn, $_POST['user_email']);
    if ($email === $_SESSION['email']) {
        $error = "Non puoi eliminare il tuo account da questa interfaccia.";
    } else {
        mysqli_query($conn, "DELETE FROM utente WHERE email='$email'");
        if (mysqli_affected_rows($conn) > 0) {
            $success = "Utente eliminato con successo!";
        } else {
            $error = "Utente non trovato o già eliminato.";
        }
    }
}

// Gestione del profilo e cambio password semplificata
if ($_SERVER["REQUEST_METHOD"] == "POST" && $user && $conn) {
    if (isset($_POST['update_profile'])) {
        $nome = mysqli_real_escape_string($conn, $_POST['nome']);
        $cognome = mysqli_real_escape_string($conn, $_POST['cognome']);
        mysqli_query($conn, "UPDATE utente SET nome='$nome', cognome='$cognome' WHERE email='{$user['email']}'");
        $success = "Profilo aggiornato con successo!";
    } elseif (isset($_POST['change_password'])) {
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
            $hash = password_hash($new, PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE utente SET password='$hash' WHERE email='{$user['email']}'");
            $_SESSION['password'] = $hash;
            $success = "Password aggiornata con successo!";
        }
    }
}

// Handle Add Service semplificato
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

// Handle Delete Service semplificato
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

        <div id="messagesGlobal"></div> <!-- Per messaggi globali di successo/errore -->

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

        <!-- Profile Section -->
        <div class="admin-section active" id="profileSection">
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

                    <button type="submit" name="change_password" class="submit-btn">Cambia Password</button>
                </form>
            </div>
        </div>        <!-- Services Section -->
        <div class="admin-section" id="servicesSection">
            <h2>Gestione Servizi</h2>

            <!-- Add Service Form -->
            <div class="form-section">
                <h3>Aggiungi Nuovo Servizio</h3>
                <div id="messagesService"></div> <!-- Specific messages for service operations -->
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

            <!-- Services Table -->
            <div class="table-section">
                <h3>Elenco Servizi</h3>
                <table id="servicesTable" class="admin-table"> <!-- Changed ID to servicesTable -->                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Categoria</th>
                            <th>Descrizione</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($conn) { // Check if $conn is valid
                            $services_query = "SELECT * FROM servizi ORDER BY id DESC";
                            $services_result = mysqli_query($conn, $services_query);
                            if ($services_result && mysqli_num_rows($services_result) > 0) {
                                while ($service = mysqli_fetch_assoc($services_result)) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($service['nome']) . "</td>";
                                    echo "<td>" . htmlspecialchars($service['categoria']) . "</td>";
                                    echo "<td>" . htmlspecialchars($service['descrizione']) . "</td>";
                                    echo "<td>";
                                    // Delete Service Form
                                    echo "<form method='POST' action='' style='display:inline-block;'>";
                                    echo "<input type='hidden' name='service_id' value='" . $service['id'] . "'>";
                                    echo "<button type='submit' name='delete_service' class='delete-btn' onclick='return confirm(\"Sei sicuro di voler eliminare questo servizio?\")'>Elimina</button>";
                                    echo "</form>";
                                    // Placeholder for Edit button
                                    // echo "<button class='edit-btn' onclick='editService(" . $service['id'] . ")'>Modifica</button>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4'>Nessun servizio trovato.</td></tr>";
                            }
                            // mysqli_close($conn) should not be here if $conn is used later in the page or by other sections.
                            // It's better to close it at the very end of the script if it's a global connection for the page.
                            // However, the user list and product list were closing their own connections.
                            // For consistency and to ensure $conn is available for all operations on this page,
                            // we should rely on the main $conn established at the top.
                        } else {
                            echo "<tr><td colspan='4'>Errore di connessione al database.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>        </div>

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
                            <th>Amministratore</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($conn) {
                            $users_query = "SELECT * FROM utente ORDER BY nome ASC";
                            $users_result = mysqli_query($conn, $users_query);
                            if ($users_result && mysqli_num_rows($users_result) > 0) {
                                while ($row = mysqli_fetch_assoc($users_result)) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['nome']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['cognome']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                    echo "<td>" . ($row['amministratore'] ? 'Sì' : 'No') . "</td>";
                                    echo "<td>";
                                    if ($row['email'] !== $_SESSION['email']) {
                                        echo "<form method='POST' action='' style='display:inline-block;'>";
                                        echo "<input type='hidden' name='user_email' value='" . htmlspecialchars($row['email']) . "'>";
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

