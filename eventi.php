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

// Array di eventi di esempio
$events = [
    [
        'id' => 1,
        'title' => 'Bike To School Day 2025',
        'date' => '2025-05-20',
        'location' => 'Piazza Castello, Torino',
        'description' => 'Una giornata dedicata alla promozione della mobilità sostenibile nelle scuole. Partecipa con la tua bici e unisciti a centinaia di studenti per sensibilizzare sull\'importanza del trasporto eco-friendly.',
        'image' => 'assets/hero-image.jpg',
        'time' => '08:00 - 17:00',
        'participants' => 250
    ],
    [
        'id' => 2,
        'title' => 'Workshop: Sicurezza Stradale',
        'date' => '2025-06-15',
        'location' => 'Parco del Valentino, Torino',
        'description' => 'Workshop pratico sulla sicurezza stradale e le migliori pratiche per ciclisti urbani. Esperti del settore condivideranno consigli e tecniche per pedalare in città in modo sicuro.',
        'image' => 'assets/community-image.jpg',
        'time' => '14:30 - 18:30',
        'participants' => 100
    ],
    [
        'id' => 3,
        'title' => 'Critical Mass Torino',
        'date' => '2025-07-01',
        'location' => 'Piazza San Carlo, Torino',
        'description' => 'Unisciti alla Critical Mass mensile di Torino. Un\'occasione per pedalare insieme e promuovere l\'uso della bicicletta come mezzo di trasporto sostenibile in città.',
        'image' => 'assets/product1.jpg',
        'time' => '19:00 - 22:00',
        'participants' => 500
    ]
];
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventi - TorollerCollective</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap" rel="stylesheet">
    <link href="style/eventi.css" rel="stylesheet">
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
                <a class="nav-link active" href="eventi.php">Eventi</a>
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
        <a class="nav-link active" href="eventi.php">Eventi</a>
        
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

    <div class="events-hero">
        <h1 class="events-hero-title">Eventi TorollerCollective</h1>
        <p class="events-hero-description">Partecipa ai nostri eventi per connetterti con altri appassionati, imparare nuove competenze e contribuire a rendere la nostra città più sostenibile.</p>
    </div>

    <div class="events-container">
        <div class="events-grid">
            <?php foreach ($events as $event): ?>
            <div class="event-card">
                <img src="<?php echo htmlspecialchars($event['image']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>" class="event-image">
                <div class="event-content">
                    <div class="event-info">
                        <div class="event-date"><?php echo date('d/m/Y', strtotime($event['date'])); ?></div>
                        <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                        <div class="event-location">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 1a5 5 0 0 0-5 5c0 5 5 10 5 10s5-5 5-10a5 5 0 0 0-5-5zm0 7.5a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5z"/>
                            </svg>
                            <?php echo htmlspecialchars($event['location']); ?>
                        </div>
                        <p class="event-description"><?php echo htmlspecialchars($event['description']); ?></p>
                        <div class="event-footer">
                            <div class="event-time"><?php echo htmlspecialchars($event['time']); ?></div>
                            <div class="event-participants"><?php echo htmlspecialchars($event['participants']); ?> partecipanti</div>
                        </div>
                    </div>
                    <div class="join-event-btn-container">
                        <a href="evento-dettaglio.php?id=<?php echo $event['id']; ?>" class="join-event-btn">Partecipa</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
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