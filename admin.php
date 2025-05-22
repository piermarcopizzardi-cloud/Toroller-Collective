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
try {
    $conn = connetti("toroller");
    if (!$conn) {
        throw new Exception("Errore di connessione al database");
    }
} catch (Exception $e) {
    error_log("Errore database: " . $e->getMessage());
}

// Gestione Eventi
if (isset($_POST['add_event'])) {
    $titolo = mysqli_real_escape_string($conn, $_POST['event_title']);
    $data = $_POST['event_date'];
    $luogo = mysqli_real_escape_string($conn, $_POST['event_location']);
    $descrizione = mysqli_real_escape_string($conn, $_POST['event_description']);
    
    $query = "INSERT INTO eventi (titolo, data, descrizione, luogo) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssss", $titolo, $data, $descrizione, $luogo);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = "Evento aggiunto con successo!";
    } else {
        $error = "Errore durante l'aggiunta dell'evento: " . mysqli_error($conn);
    }
}

if (isset($_POST['delete_event']) && isset($_POST['event_id'])) {
    $event_id = intval($_POST['event_id']);
    
    $query = "DELETE FROM eventi WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $event_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = "Evento eliminato con successo!";
    } else {
        $error = "Errore durante l'eliminazione dell'evento: " . mysqli_error($conn);
    }
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
        
        // Verifica che la password attuale sia corretta
        $query = "SELECT password FROM utente WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user_data = mysqli_fetch_assoc($result);
        
        if (password_verify($currentPassword, $user_data['password'])) {
            if ($newPassword === $confirmPassword) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateQuery = "UPDATE utente SET password = ? WHERE email = ?";
                $stmt = mysqli_prepare($conn, $updateQuery);
                mysqli_stmt_bind_param($stmt, "ss", $hashedPassword, $email);
                
                if (mysqli_stmt_execute($stmt)) {
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

// Gestione Prodotti
// Gestione prodotti spostata in admin_actions.php

// Gestione Utenti
if (isset($_POST['delete_user'])) {
    if (isset($_POST['user_email'])) {
        $user_email = mysqli_real_escape_string($conn, $_POST['user_email']);
        
        // Non permettere l'eliminazione del proprio account
        if ($user_email !== $_SESSION['email']) {
            $query = "DELETE FROM utente WHERE email = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "s", $user_email);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = "Utente eliminato con successo!";
            } else {
                $error = "Errore nell'eliminazione dell'utente.";
            }
        } else {
            $error = "Non puoi eliminare il tuo account!";
        }
    }
}

// Aggiungiamo lo script per la gestione asincrona dei prodotti
?>
<script>
function showMessage(message, isError = false) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert ${isError ? 'alert-danger' : 'alert-success'} alert-dismissible fade show`;
    alertDiv.role = 'alert';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    document.querySelector('#messages').appendChild(alertDiv);
    
    // Rimuovi il messaggio dopo 5 secondi
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
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
            showMessage(data.message);
            // Aggiungi il nuovo prodotto alla tabella
            const productsTable = document.querySelector('#productsTable tbody');
            const newRow = document.createElement('tr');
            newRow.id = `product-${data.product.id}`;
            newRow.innerHTML = `
                <td>${data.product.tipologia}</td>
                <td>${data.product.prezzo}</td>
                <td>${data.product.quantita}</td>
                <td>${data.product.colore}</td>
                <td><img src="assets/products/${data.product.immagine}" height="50"></td>
                <td>${data.product.descrizione}</td>
                <td>
                    <button class="btn btn-danger btn-sm" onclick="deleteProduct(${data.product.id})">
                        <i class="fas fa-trash"></i> Elimina
                    </button>
                </td>
            `;
            productsTable.appendChild(newRow);
            form.reset();
        } else {
            showMessage(data.error, true);
        }
    })
    .catch(error => {
        showMessage('Si è verificato un errore durante l\'aggiunta del prodotto', true);
        console.error('Error:', error);
    });
}

function deleteProduct(id) {
    if (!confirm('Sei sicuro di voler eliminare questo prodotto?')) {
        return;
    }
    
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
            showMessage(data.message);
            // Rimuovi la riga dalla tabella
            document.querySelector(`#product-${id}`).remove();
        } else {
            showMessage(data.error, true);
        }
    })
    .catch(error => {
        showMessage('Si è verificato un errore durante l\'eliminazione del prodotto', true);
        console.error('Error:', error);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Aggiungi validazione al form dei prodotti
    const productForm = document.querySelector('#productForm');
    if (productForm) {
        productForm.addEventListener('submit', addProduct);
    }
});
</script>
<?php
// Gestione Eventi
if (isset($_POST['add_event'])) {
    $titolo = mysqli_real_escape_string($conn, $_POST['event_title']);
    $data = $_POST['event_date'];
    $descrizione = mysqli_real_escape_string($conn, $_POST['event_description']);
    
    // Gestione dell'upload dell'immagine
    if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['event_image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $target_dir = "assets/events/";
            $new_filename = uniqid() . '.' . $ext;
            $target_path = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['event_image']['tmp_name'], $target_path)) {
                $query = "INSERT INTO eventi (titolo, data, descrizione, immagine) VALUES (?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "ssss", $titolo, $data, $descrizione, $new_filename);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success = "Evento aggiunto con successo!";
                } else {
                    $error = "Errore nell'aggiunta dell'evento.";
                }
            } else {
                $error = "Errore nel caricamento dell'immagine.";
            }
        } else {
            $error = "Tipo di file non supportato.";
        }
    }
}

if (isset($_POST['delete_event'])) {
    if (isset($_POST['event_id'])) {
        $event_id = intval($_POST['event_id']);
        
        // Prima eliminiamo l'immagine
        $query = "SELECT immagine FROM eventi WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $event_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($event = mysqli_fetch_assoc($result)) {
            $image_path = "assets/events/" . $event['immagine'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        // Poi eliminiamo l'evento dal database
        $query = "DELETE FROM eventi WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $event_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = "Evento eliminato con successo!";
        } else {
            $error = "Errore nell'eliminazione dell'evento.";
        }
    }
}

// Ottieni le informazioni dell'utente se è loggato
$userEmail = '';
$userName = '';
$cartItems = [];
$cartTotal = 0;

if ($isLoggedIn && $conn) {
    $email = mysqli_real_escape_string($conn, $_SESSION['email']);
    $query = "SELECT nome, email FROM utente WHERE email = '$email'";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $userEmail = $user['email'];
        $userName = $user['nome'];
    }

    // Ottieni il contenuto del carrello per l'utente loggato
    $cartQuery = "SELECT c.id, c.quantita, p.tipologia as name, p.prezzo as price, p.id as product_id 
                 FROM carrello c 
                 JOIN prodotti p ON c.id_prodotto = p.id 
                 WHERE c.email_utente = '$email'";
    $cartResult = mysqli_query($conn, $cartQuery);
    
    if ($cartResult) {
        while ($row = mysqli_fetch_assoc($cartResult)) {
            $cartItems[] = $row;
            $cartTotal += $row['price'] * $row['quantita'];
        }
    }
}

if ($conn) {
    mysqli_close($conn);
}

// Verify if user is logged in
if (!isset($_SESSION['email']) || !isset($_SESSION['password'])) {
    header("Location: login.php");
    exit();
}

$userEmail = $_SESSION['email'];
$error = "";
$success = "";

// Get user data
$conn = connetti("toroller");
$email = mysqli_real_escape_string($conn, $_SESSION['email']);
$query = "SELECT * FROM utente WHERE email = '$email'";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);


// Handle profile update
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

    <div class="mobile-menu">
        <div class="close-menu">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="24" height="24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </div>
        <a class="nav-link" href="index.php">Home</a>
        <a class="nav-link" href="community.php">Community</a>
        <a class="nav-link" href="shop.php">Shop</a>
        <a class="nav-link" href="eventi.php">Eventi</a>
        
        <div class="user-menu">
            <span class="user-email"><?php echo htmlspecialchars($userEmail); ?></span>
            <a href="index.php?logout=1" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="profile-container">
        <h1 class="profile-title">Pannello di Amministrazione</h1>

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
        </div>            <!-- Products Section -->
        <div class="admin-section" id="productsSection">
            <h2>Gestione Prodotti</h2>
            
            <!-- Add Product Form -->
            <div class="form-section">
                <h3>Aggiungi Nuovo Prodotto</h3>
                <div id="messages"></div>
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
                            <input type="number" step="0.01" min="0" name="prezzo" class="form-input" required>
                            <div class="invalid-feedback">
                                Inserisci un prezzo valido
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Quantità</label>
                            <input type="number" min="0" name="quantita" class="form-input" required>
                            <div class="invalid-feedback">
                                La quantità è obbligatoria
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Colore</label>
                            <input type="text" name="colore" class="form-input" required>
                            <div class="invalid-feedback">
                                Il colore è obbligatorio
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Descrizione</label>
                            <textarea name="descrizione" class="form-textarea" required></textarea>
                            <div class="invalid-feedback">
                                La descrizione è obbligatoria
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Immagine</label>
                            <input type="file" name="immagine" class="form-input" accept="image/*" required>
                            <div class="invalid-feedback">
                                L'immagine è obbligatoria
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="submit-btn">Aggiungi Prodotto</button>
                </form>
            </div>

            <!-- Product List -->
            <div class="data-section">
                <h3>Lista Prodotti</h3>
                <div class="table-responsive">
                    <table id="productsTable" class="data-table">
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
                            $products_query = "SELECT * FROM prodotti ORDER BY id";
                            $products_result = mysqli_query($conn, $products_query);
                            while ($product = mysqli_fetch_assoc($products_result)): 
                            ?>
                            <tr id="product-<?php echo $product['id']; ?>">
                                <td><?php echo htmlspecialchars($product['tipologia']); ?></td>
                                <td>€<?php echo number_format($product['prezzo'], 2, ',', '.'); ?></td>
                                <td><?php echo htmlspecialchars($product['quantita']); ?></td>
                                <td><?php echo htmlspecialchars($product['colore']); ?></td>
                                <td><img src="assets/products/<?php echo htmlspecialchars($product['immagine']); ?>" height="50" alt="<?php echo htmlspecialchars($product['tipologia']); ?>"></td>
                                <td><?php echo htmlspecialchars($product['descrizione']); ?></td>
                                <td>
                                    <button class="btn btn-danger btn-sm" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-trash"></i> Elimina
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Users Section -->
        <div class="admin-section" id="usersSection">
            <h2>Gestione Utenti</h2>
            <div class="data-section">
                <h3>Lista Utenti</h3>
                <form method="POST" action="">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Seleziona</th>
                                <th>Email</th>
                                <th>Nome</th>
                                <th>Cognome</th>
                                <th>Data di Nascita</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $users_query = "SELECT * FROM utente ORDER BY email";
                            $users_result = mysqli_query($conn, $users_query);
                            while ($user = mysqli_fetch_assoc($users_result)): 
                            ?>
                            <tr>
                                <td><input type="radio" name="user_email" value="<?php echo $user['email']; ?>"></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['nome']); ?></td>
                                <td><?php echo htmlspecialchars($user['cognome']); ?></td>
                                <td><?php echo htmlspecialchars($user['data_nascita']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <button type="submit" name="delete_user" class="delete-btn">Elimina Selezionato</button>
                </form>
            </div>
        </div>

        <!-- Events Section -->
        <div class="admin-section" id="eventsSection">
            <h2>Gestione Eventi</h2>
            
            <!-- Add Event Form -->
            <div class="form-section">
                <h3>Aggiungi Nuovo Evento</h3>
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Titolo</label>
                            <input type="text" name="event_title" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Data</label>
                            <input type="date" name="event_date" class="form-input" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Luogo</label>
                            <input type="text" name="event_location" class="form-input" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Descrizione</label>
                            <textarea name="event_description" class="form-textarea" required></textarea>
                        </div>
                    </div>
                    <button type="submit" name="add_event" class="submit-btn">Aggiungi Evento</button>
                </form>
            </div>

            <!-- Events List -->
            <div class="data-section">
                <h3>Lista Eventi</h3>
                <form method="POST" action="">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Seleziona</th>
                                <th>Titolo</th>
                                <th>Data</th>
                                <th>Luogo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $events_query = "SELECT * FROM eventi ORDER BY data DESC";
                            $events_result = mysqli_query($conn, $events_query);
                            while ($event = mysqli_fetch_assoc($events_result)): 
                            ?>
                            <tr>
                                <td><input type="radio" name="event_id" value="<?php echo $event['id']; ?>"></td>
                                <td><?php echo htmlspecialchars($event['titolo']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($event['data'])); ?></td>
                                <td><?php echo htmlspecialchars($event['luogo']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <button type="submit" name="delete_event" class="delete-btn">Elimina Selezionato</button>
                </form>
            </div>
        </div>

        <script>
        function addEvent(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            
            fetch('admin_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Aggiunge il nuovo evento alla tabella
                    const tbody = document.querySelector('#eventsList tbody');
                    const tr = document.createElement('tr');
                    tr.id = `event-${data.event.id}`;
                    tr.innerHTML = `
                        <td>${data.event.id}</td>
                        <td>${data.event.titolo}</td>
                        <td>${new Date(data.event.data).toLocaleDateString('it-IT')}</td>
                        <td>${data.event.luogo}</td>
                        <td>
                            <button onclick="deleteEvent(${data.event.id})" class="delete-btn">Elimina</button>
                        </td>
                    `;
                    tbody.insertBefore(tr, tbody.firstChild);
                    e.target.reset();
                    alert('Evento aggiunto con successo!');
                } else {
                    alert(data.error || 'Errore durante l\'aggiunta dell\'evento');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Errore durante l\'aggiunta dell\'evento');
            });
        }

        function deleteEvent(id) {
            if (!confirm('Sei sicuro di voler eliminare questo evento?')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete_event');
            formData.append('event_id', id);

            fetch('admin_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById(`event-${id}`).remove();
                    alert('Evento eliminato con successo!');
                } else {
                    alert(data.error || 'Errore durante l\'eliminazione dell\'evento');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Errore durante l\'eliminazione dell\'evento');
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const eventForm = document.getElementById('eventForm');
            if (eventForm) {
                eventForm.addEventListener('submit', addEvent);
            }
        });
        </script>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hamburger = document.querySelector('.hamburger-menu');
            const closeMenu = document.querySelector('.close-menu');
            const mobileMenu = document.querySelector('.mobile-menu');
            const mobileLinks = document.querySelectorAll('.mobile-menu .nav-link, .mobile-menu .user-menu a');

            function toggleMenu() {
                mobileMenu.classList.toggle('active');
                document.body.style.overflow = mobileMenu.classList.contains('active') ? 'hidden' : '';
            }

            hamburger.addEventListener('click', toggleMenu);
            closeMenu.addEventListener('click', toggleMenu);

            // Close menu when clicking on links
            mobileLinks.forEach(link => {
                link.addEventListener('click', toggleMenu);
            });

            // Tab Navigation
            const tabs = document.querySelectorAll('.admin-tab');
            const sections = document.querySelectorAll('.admin-section');

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    // Remove active class from all tabs and sections
                    tabs.forEach(t => t.classList.remove('active'));
                    sections.forEach(s => s.classList.remove('active'));

                    // Add active class to clicked tab and corresponding section
                    tab.classList.add('active');
                    const sectionId = tab.getAttribute('data-tab') + 'Section';
                    document.getElementById(sectionId).classList.add('active');
                });
            });

            // Product editing functionality
            window.editProduct = function(productId) {
                // Implement product editing logic here
                console.log('Editing product:', productId);
            };
        });

        function toggleCart() {
            const cartPopup = document.getElementById('cartPopup');
            cartPopup.classList.toggle('active');
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Gestione del form di aggiunta evento
            const addEventForm = document.getElementById('addEventForm');
            addEventForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('action', 'add_event');
                
                fetch('admin_actions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Aggiungi la nuova riga alla tabella
                        const tbody = document.querySelector('#eventsList tbody');
                        const newRow = document.createElement('tr');
                        newRow.dataset.eventId = data.event.id;
                        newRow.innerHTML = `
                            <td>${data.event.id}</td>
                            <td>${data.event.titolo}</td>
                            <td>${formatDate(data.event.data)}</td>
                            <td>${data.event.luogo}</td>
                            <td>
                                <button onclick="deleteEvent(${data.event.id})" class="delete-btn">Elimina</button>
                            </td>
                        `;
                        tbody.insertBefore(newRow, tbody.firstChild);
                        
                        // Reset del form
                        addEventForm.reset();
                        
                        // Mostra messaggio di successo
                        alert('Evento aggiunto con successo!');
                    } else {
                        alert(data.error || 'Errore durante l\'aggiunta dell\'evento');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Errore durante l\'aggiunta dell\'evento');
                });
            });
        });

        function deleteEvent(eventId) {
            if (!confirm('Sei sicuro di voler eliminare questo evento?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete_event');
            formData.append('event_id', eventId);
            
            fetch('admin_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Rimuovi la riga dalla tabella
                    const row = document.querySelector(`tr[data-event-id="${eventId}"]`);
                    if (row) {
                        row.remove();
                    }
                    alert('Evento eliminato con successo!');
                } else {
                    alert(data.error || 'Errore durante l\'eliminazione dell\'evento');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Errore durante l\'eliminazione dell\'evento');
            });
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('it-IT');
        }
    </script>
    <script src="<?php echo $basePath; ?>/components/header.js"></script>
</body>
</html>