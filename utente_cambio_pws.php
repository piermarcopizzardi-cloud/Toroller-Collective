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

// Redirect admin users to admin.php
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
    header("Location: admin.php");
    exit();
}

$userEmail = $_SESSION['email'];
$error = "";
$success = "";

// Handle password change
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = connetti("toroller");
    $currentPassword = mysqli_real_escape_string($conn, $_POST["current_password"]);
    $newPassword = mysqli_real_escape_string($conn, $_POST["new_password"]);
    $confirmPassword = mysqli_real_escape_string($conn, $_POST["confirm_password"]);
    
    // Verify current password
    $query = "SELECT password FROM utente WHERE email = '$userEmail'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($currentPassword, $user['password'])) {
            if ($newPassword === $confirmPassword) {
                // Update password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateQuery = "UPDATE utente SET password = '$hashedPassword' WHERE email = '$userEmail'";
                
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
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilo Utente - TorollerCollective</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            width: 100%;
            min-height: 100vh;
            background-color: #ffffff;
        }

        .header {
            width: 100%;
            height: 118px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-left: 110px;
            padding-right: 110px;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-text {
            color: #04CD00;
            font-size: 30px;
            font-weight: 800;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 33px;
        }

        .nav-link {
            color: #BDD3C6;
            font-size: 18px;
            text-decoration: none;
        }

        .nav-link.active {
            color: #04CD00;
            font-weight: 600;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 8px 16px;
            border: 1px solid #7FE47E;
            border-radius: 30px;
        }

        .user-email {
            color: #04CD00;
            font-size: 16px;
            font-weight: 600;
        }

        .logout-btn {
            color: #BDD3C6;
            text-decoration: none;
            font-size: 14px;
        }

        .profile-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 30px;
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .profile-title {
            color: #04CD00;
            font-size: 24px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }

        .form-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            background-color: #04CD00;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }

        .error-message {
            color: #ff0000;
            margin-bottom: 15px;
        }

        .success-message {
            color: #04CD00;
            margin-bottom: 15px;
        }

        .hamburger-menu {
            display: none;
        }

        @media (max-width: 991px) {
            .header {
                padding-left: 40px;
                padding-right: 40px;
            }

            .profile-container {
                margin: 40px;
            }
        }
        
        @media (max-width: 640px) {
            .header {
                padding-left: 20px;
                padding-right: 20px;
            }

            .nav-menu {
                display: none;
            }

            .hamburger-menu {
                display: block;
                color: #04CD00;
                cursor: pointer;
                z-index: 1001;
            }

            .profile-container {
                margin: 20px;
            }
        }

        .mobile-menu {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.98);
            z-index: 1000;
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 24px;
        }

        .mobile-menu.active {
            display: flex;
        }

        .mobile-menu .nav-link {
            font-size: 24px;
            padding: 12px;
        }

        .mobile-menu .auth-buttons {
            flex-direction: column;
            margin-top: 24px;
        }

        .close-menu {
            position: absolute;
            top: 32px;
            right: 32px;
            cursor: pointer;
            color: #04CD00;
        }

        /* Cart styles */
        .cart-container {
            position: relative;
            display: inline-block;
        }

        .cart-icon {
            cursor: pointer;
            position: relative;
            padding: 8px;
        }

        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #FF0000;
            color: #FFFFFF;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
        }

        .cart-popup {
            position: absolute;
            top: 100%;
            right: 0;
            width: 300px;
            background-color: #FFFFFF;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: none;
        }

        .cart-popup.active {
            display: block;
        }

        .cart-popup-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            border-bottom: 1px solid #E5E7EB;
        }

        .cart-popup-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        .close-cart {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .cart-items {
            max-height: 300px;
            overflow-y: auto;
            padding: 16px;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #E5E7EB;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-item-name {
            font-weight: 600;
            margin-bottom: 4px;
        }

        .cart-item-price {
            color: #6B7280;
            font-size: 14px;
        }

        .remove-item {
            background: none;
            border: none;
            color: #FF0000;
            cursor: pointer;
            font-size: 18px;
            padding: 4px 8px;
        }

        .cart-footer {
            padding: 16px;
            border-top: 1px solid #E5E7EB;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cart-total {
            font-weight: 600;
        }

        .checkout-btn {
            background-color: #04CD00;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
        }

        .empty-cart {
            text-align: center;
            color: #6B7280;
            padding: 20px;
        }
    </style>
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
            
            <div class="auth-buttons">
                <?php if ($isLoggedIn): ?>
                <div class="user-menu">
                    <a href="utente_cambio_pws.php" class="user-email"><?php echo htmlspecialchars($userEmail); ?></a>
                    <a href="?logout=1" class="logout-btn">Logout</a>
                </div>
                <?php else: ?>
                <a href="login.php" class="login-btn">Login</a>
                <a href="registrazione.php" class="get-started-btn">Get started</a>
                <?php endif; ?>
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
        
        <div class="auth-buttons">
            <?php if ($isLoggedIn): ?>
            <div class="user-menu">
                <a href="utente_cambio_pws.php" class="user-email"><?php echo htmlspecialchars($userEmail); ?></a>
                <a href="?logout=1" class="logout-btn">Logout</a>
            </div>
            <?php else: ?>
            <a href="login.php" class="login-btn">Login</a>
            <a href="registrazione.php" class="get-started-btn">Get started</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="profile-container">
        <h2 class="profile-title">Modifica Password</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>

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

            <button type="submit" class="submit-btn">Aggiorna Password</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hamburger = document.querySelector('.hamburger-menu');
            const closeMenu = document.querySelector('.close-menu');
            const mobileMenu = document.querySelector('.mobile-menu');
            const mobileLinks = document.querySelectorAll('.mobile-menu .nav-link, .mobile-menu .auth-buttons a');

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
        });

        function toggleCart() {
            const cartPopup = document.getElementById('cartPopup');
            cartPopup.classList.toggle('active');
        }
    </script>
</body>
</html>