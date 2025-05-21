<?php
session_start();
include("conn.php");

// Controlla se l'utente è loggato
$isLoggedIn = isset($_SESSION['email']) && isset($_SESSION['password']);

if (!$isLoggedIn) {
    header("Location: login.php");
    exit();
}

// Stabilisci la connessione al database
$conn = connetti('toroller');

// Ottieni il contenuto del carrello
$cartItems = [];
$cartTotal = 0;
if ($conn) {
    $email = mysqli_real_escape_string($conn, $_SESSION['email']);
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

// Ottieni le informazioni dell'utente se è loggato
$userEmail = '';
if ($isLoggedIn) {
    $email = mysqli_real_escape_string($conn, $_SESSION['email']);
    $query = "SELECT email FROM utente WHERE email = '$email'";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $userEmail = $user['email'];
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - TorollerCollective</title>
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
            background-color: #F9FAFB;
            margin: 0;
            padding-top: 118px; /* Aggiunto per compensare l'header fixed */
        }

        .header {
            width: 100%;
            height: 118px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 110px;
            background-color: #ffffff;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            border-bottom: none;
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
            font-feature-settings: 'kern' 1;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .nav-link.active {
            color: #04CD00;
            font-weight: 600;
        }
        
        .nav-link-with-icon {
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .auth-buttons {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 8px 16px;
            border: 1px solid #7FE47E;
            border-radius: 30px;
            white-space: nowrap;
        }
        
        .user-email {
            color: #04CD00;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
        
        .logout-btn {
            color: #BDD3C6;
            text-decoration: none;
            font-size: 14px;
            display: inline-block;
        }

        .checkout-container {
            max-width: 1200px;
            margin: 20px auto 40px;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        .checkout-summary, .payment-section {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            color: #04CD00;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 24px;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid #E5E7EB;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-details {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .item-name {
            font-weight: 600;
            color: #333;
        }

        .item-quantity {
            color: #6B7280;
            font-size: 14px;
        }

        .item-price {
            font-weight: 600;
            color: #04CD00;
        }

        .cart-total {
            margin-top: 24px;
            padding-top: 16px;
            border-top: 2px solid #E5E7EB;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 20px;
            font-weight: 700;
        }

        .payment-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-label {
            font-weight: 600;
            color: #374151;
        }

        .form-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            font-size: 16px;
        }

        .card-details {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 16px;
        }

        .submit-button {
            background-color: #04CD00;
            color: white;
            padding: 16px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 24px;
        }

        .submit-button:hover {
            background-color: #03b600;
        }

        .payment-options {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
        }

        .payment-option {
            flex: 1;
            padding: 16px;
            border: 2px solid #E5E7EB;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-option.selected {
            border-color: #04CD00;
            background-color: #F3FFF3;
        }

        .payment-option img {
            height: 24px;
            margin-bottom: 8px;
        }

        .hamburger-menu {
            display: none;
        }

        @media (max-width: 1024px) {
            .hamburger-menu {
                display: block;
                cursor: pointer;
            }

            .nav-menu {
                display: none;
            }
            
            .checkout-container {
                grid-template-columns: 1fr;
            }
            
            .header {
                padding: 0 20px;
            }
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
            top: -5px;
            right: -5px;
            background-color: #04CD00;
            color: white;
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
                    <a class="nav-link active" href="shop.php">Shop</a>
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
            
            <?php if ($isLoggedIn): ?>
                <div class="user-menu">
                    <span class="user-email"><?php echo htmlspecialchars($_SESSION['email']); ?></span>
                    <a href="?logout=1" class="logout-btn">Logout</a>
                </div>
            <?php else: ?>
                <div class="auth-buttons">
                    <a href="login.php" class="login-btn">Login</a>
                    <a href="registrazione.php" class="get-started-btn">Get started</a>
                </div>
            <?php endif; ?>
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
        <a class="nav-link active" href="shop.php">Shop</a>
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

    <main class="checkout-container">
        <div class="checkout-summary">
            <h2 class="section-title">Riepilogo carrello</h2>
            <?php if (empty($cartItems)): ?>
                <p>Il tuo carrello è vuoto.</p>
            <?php else: ?>
                <?php foreach ($cartItems as $item): ?>
                    <div class="cart-item">
                        <div class="item-details">
                            <span class="item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                            <span class="item-quantity">Quantità: <?php echo htmlspecialchars($item['quantita']); ?></span>
                        </div>
                        <span class="item-price">€<?php echo number_format($item['price'] * $item['quantita'], 2); ?></span>
                    </div>
                <?php endforeach; ?>
                <div class="cart-total">
                    <span>Totale</span>
                    <span>€<?php echo number_format($cartTotal, 2); ?></span>
                </div>
            <?php endif; ?>
        </div>

        <div class="payment-section">
            <h2 class="section-title">Pagamento</h2>                <div class="payment-options">
                    <div class="payment-option selected">
                        <img src="https://cdn-icons-png.flaticon.com/128/179/179457.png" alt="Credit Card" style="width: 24px; height: 24px;">
                        <div>Carta di credito</div>
                    </div>
                    <div class="payment-option">
                        <img src="https://cdn-icons-png.flaticon.com/128/174/174861.png" alt="PayPal" style="width: 24px; height: 24px;">
                        <div>PayPal</div>
                    </div>
                </div>

            <form class="payment-form">
                <div class="form-group">
                    <label class="form-label">Titolare carta</label>
                    <input type="text" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Numero carta</label>
                    <input type="text" class="form-input" required pattern="[0-9]{16}">
                </div>
                <div class="card-details">
                    <div class="form-group">
                        <label class="form-label">Data di scadenza</label>
                        <input type="month" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">CVV</label>
                        <input type="text" class="form-input" required pattern="[0-9]{3,4}">
                    </div>
                </div>
                <button type="submit" class="submit-button">Completa l'acquisto</button>
            </form>
        </div>
    </main>

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
            cartPopup.style.display = cartPopup.style.display === 'block' ? 'none' : 'block';
        }

        // Chiudi il popup del carrello quando si clicca fuori
        document.addEventListener('click', function(event) {
            const cartPopup = document.getElementById('cartPopup');
            const cartIcon = document.querySelector('.cart-icon');
            
            if (!cartPopup.contains(event.target) && !cartIcon.contains(event.target)) {
                cartPopup.style.display = 'none';
            }
        });

        // Previeni la chiusura quando si clicca dentro il carrello
        document.getElementById('cartPopup').addEventListener('click', function(event) {
            event.stopPropagation();
        });

        function selectPaymentMethod(method) {
            const options = document.querySelectorAll('.payment-option');
            options.forEach(option => option.classList.remove('selected'));
            
            if (method === 'card') {
                document.querySelector('.payment-option:first-child').classList.add('selected');
                document.getElementById('payment-form').style.display = 'flex';
            } else {
                document.querySelector('.payment-option:last-child').classList.add('selected');
                document.getElementById('payment-form').style.display = 'none';
                // In una implementazione reale, qui verrebbe attivato Apple Pay
                alert('Apple Pay sarà implementato prossimamente');
            }
        }

        // Per ora preveniamo solo l'invio del form
        document.getElementById('payment-form').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Il sistema di pagamento sarà implementato prossimamente con Stripe');
        });
    </script>
</body>
</html>