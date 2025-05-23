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

try {
    $conn = connetti("toroller");
    if (!$conn) {
        throw new Exception("Errore di connessione al database");
    }
} catch (Exception $e) {
    error_log("Errore database: " . $e->getMessage());
    $error = "Errore di connessione al database.";
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
                <h3>Aggiungi Nuovo Evento</h3>
                <div id="messagesEvent">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                </div>
                <form id="eventForm" method="POST" action="admin.php" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="form-label">Titolo</label>
                        <input type="text" name="titolo" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Data</label>
                        <input type="date" name="data" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Luogo</label>
                        <input type="text" name="luogo" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Descrizione</label>
                        <textarea name="descrizione" class="form-input" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Immagine (opzionale)</label>
                        <input type="file" name="immagine" class="form-input" accept="image/*">
                    </div>
                    <button type="submit" name="add_event" class="submit-btn">Aggiungi Evento</button>
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
                                        echo "<img src='assets/events/" . htmlspecialchars($event['immagine']) . "' height='50'>";
                                    } else {
                                        echo "N/A";
                                    }
                                    echo "</td>";
                                    echo "<td>";
                                    echo "<form method='POST' action='admin.php' style='display:inline-block;'>";
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
</body>
</html>