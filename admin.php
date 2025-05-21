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
if (isset($_POST['add_product'])) {
    $tipologia = mysqli_real_escape_string($conn, $_POST['product_name']);
    $prezzo = floatval($_POST['product_price']);
    $descrizione = mysqli_real_escape_string($conn, $_POST['product_description']);
    
    // Gestione dell'upload dell'immagine
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['product_image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $target_dir = "assets/products/";
            $new_filename = uniqid() . '.' . $ext;
            $target_path = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_path)) {
                $query = "INSERT INTO prodotti (tipologia, prezzo, descrizione, immagine) VALUES (?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "sdss", $tipologia, $prezzo, $descrizione, $new_filename);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success = "Prodotto aggiunto con successo!";
                } else {
                    $error = "Errore nell'aggiunta del prodotto.";
                }
            } else {
                $error = "Errore nel caricamento dell'immagine.";
            }
        } else {
            $error = "Tipo di file non supportato.";
        }
    }
}

if (isset($_POST['delete_product'])) {
    if (isset($_POST['product_id'])) {
        $product_id = intval($_POST['product_id']);
        
        // Prima eliminiamo l'immagine
        $query = "SELECT immagine FROM prodotti WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $product_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($product = mysqli_fetch_assoc($result)) {
            $image_path = "assets/products/" . $product['immagine'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        // Poi eliminiamo il prodotto dal database
        $query = "DELETE FROM prodotti WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $product_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = "Prodotto eliminato con successo!";
        } else {
            $error = "Errore nell'eliminazione del prodotto.";
        }
    }
}

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
    <link href="style/admin.css" rel="stylesheet">
</head>
<body>
    <div class="header">
        <div class="logo-container">
            <img src="assets/logo1.jpg" alt="TorollerCollective Logo" width="80" height="80" style="object-fit: contain;">
            <div class="logo-text">TorollerCollective</div>
        </div>
        
        <div class="nav-menu">
            <div class="nav-links">
                <a class="nav-link" href="index.php">Home</a>
                <a class="nav-link" href="community.php">Community</a>
                <div class="nav-link-with-icon">
                    <a class="nav-link" href="shop.php">Shop</a>
                    <div class="cart-container">
                        <div class="cart-icon" onclick="toggleCart()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="9" cy="21" r="1"></circle>
                                <circle cx="20" cy="21" r="1"></circle>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                            </svg>
                            <span class="cart-badge"><?php echo array_sum(array_column($cartItems, 'quantita')); ?></span>
                        </div>
                        
                        <!-- Cart Popup -->
                        <div id="cartPopup" class="cart-popup">
                            <div class="cart-popup-header">
                                <h3>Il tuo carrello</h3>
                                <span class="close-cart" onclick="toggleCart()">&times;</span>
                            </div>
                            <div class="cart-items">
                                <?php if (!empty($cartItems)): ?>
                                    <?php foreach ($cartItems as $item): ?>
                                        <div class="cart-item">
                                            <div>
                                                <div class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                                <div class="cart-item-price">€<?php echo number_format($item['price'], 2, ',', '.'); ?> x <?php echo $item['quantita']; ?></div>
                                            </div>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="cart_item_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" name="remove_from_cart" class="remove-item">&times;</button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="empty-cart">Il carrello è vuoto</p>
                                <?php endif; ?>
                            </div>
                            <div class="cart-footer">
                                <div class="cart-total">Totale: €<?php echo number_format($cartTotal, 2, ',', '.'); ?></div>
                                <?php if (!empty($cartItems)): ?>
                                    <a href="checkout.php" class="checkout-btn">Procedi all'acquisto</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <a class="nav-link" href="eventi.php">Eventi</a>
            </div>
            
            <div class="user-menu">
                <span class="user-email"><?php echo htmlspecialchars($userEmail); ?></span>
                <a href="index.php?logout=1" class="logout-btn">Logout</a>
            </div>
        </div>

        <div class="hamburger-menu">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="24" height="24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </div>
    </div>

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
        </div>

        <!-- Products Section -->
        <div class="admin-section" id="productsSection">
            <h2>Gestione Prodotti</h2>
            
            <!-- Add Product Form -->
            <div class="form-section">
                <h3>Aggiungi Nuovo Prodotto</h3>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Tipologia</label>
                            <input type="text" name="product_name" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Prezzo</label>
                            <input type="number" step="0.01" name="product_price" class="form-input" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Descrizione</label>
                            <textarea name="product_description" class="form-textarea" required></textarea>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Immagine</label>
                            <input type="file" name="product_image" class="form-input" required>
                        </div>
                    </div>
                    <button type="submit" name="add_product" class="submit-btn">Aggiungi Prodotto</button>
                </form>
            </div>

            <!-- Product List -->
            <div class="data-section">
                <h3>Lista Prodotti</h3>
                <form method="POST" action="">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Seleziona</th>
                                <th>ID</th>
                                <th>Tipologia</th>
                                <th>Prezzo</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $products_query = "SELECT * FROM prodotti ORDER BY id";
                            $products_result = mysqli_query($conn, $products_query);
                            while ($product = mysqli_fetch_assoc($products_result)): 
                            ?>
                            <tr>
                                <td><input type="radio" name="product_id" value="<?php echo $product['id']; ?>"></td>
                                <td><?php echo $product['id']; ?></td>
                                <td><?php echo htmlspecialchars($product['tipologia']); ?></td>
                                <td>€<?php echo number_format($product['prezzo'], 2, ',', '.'); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button type="button" class="edit-btn" onclick="editProduct(<?php echo $product['id']; ?>)">Modifica</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <button type="submit" name="delete_product" class="delete-btn">Elimina Selezionato</button>
                </form>
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
                <form method="POST" action="" enctype="multipart/form-data">
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
                            <label class="form-label">Descrizione</label>
                            <textarea name="event_description" class="form-textarea" required></textarea>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Immagine</label>
                            <input type="file" name="event_image" class="form-input" required>
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
                                <th>ID</th>
                                <th>Titolo</th>
                                <th>Data</th>
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
                                <td><?php echo $event['id']; ?></td>
                                <td><?php echo htmlspecialchars($event['titolo']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($event['data'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <button type="submit" name="delete_event" class="delete-btn">Elimina Selezionato</button>
                </form>
            </div>
        </div>
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
    </script>
</body>
</html>