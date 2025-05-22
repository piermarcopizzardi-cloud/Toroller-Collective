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
if ($isLoggedIn && $conn) {
    $email = mysqli_real_escape_string($conn, $_SESSION['email']);
    $query = "SELECT nome, email FROM utente WHERE email = '$email'";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $userEmail = $user['email'];
        $userName = $user['nome'];
    }
}

// Ajax cart handling
if (isset($_POST['action']) && $isLoggedIn) {
    $response = ['success' => false];
    $email = mysqli_real_escape_string($conn, $_SESSION['email']);

    switch ($_POST['action']) {
        case 'add_to_cart':
            if (isset($_POST['product_id'])) {
                $productId = (int)$_POST['product_id'];
                
                // Check if product exists in cart
                $query = "SELECT id, quantita FROM carrello WHERE email_utente = '$email' AND id_prodotto = $productId";
                $result = mysqli_query($conn, $query);
                
                if (mysqli_num_rows($result) > 0) {
                    // Update quantity
                    $row = mysqli_fetch_assoc($result);
                    $newQuantity = $row['quantita'] + 1;
                    $success = mysqli_query($conn, "UPDATE carrello SET quantita = $newQuantity WHERE id = " . $row['id']);
                } else {
                    // Add new product to cart
                    $success = mysqli_query($conn, "INSERT INTO carrello (email_utente, id_prodotto, quantita) VALUES ('$email', $productId, 1)");
                }
                
                if ($success) {
                    // Get updated cart info
                    $cartQuery = "SELECT SUM(quantita) as total FROM carrello WHERE email_utente = '$email'";
                    $cartResult = mysqli_query($conn, $cartQuery);
                    $cartTotal = mysqli_fetch_assoc($cartResult)['total'] ?? 0;
                    
                    $response = [
                        'success' => true,
                        'cartTotal' => $cartTotal
                    ];
                }
            }
            break;
            
        case 'remove_from_cart':
            if (isset($_POST['cart_item_id'])) {
                $cartItemId = (int)$_POST['cart_item_id'];
                $success = mysqli_query($conn, "DELETE FROM carrello WHERE id = $cartItemId AND email_utente = '$email'");
                
                if ($success) {
                    // Get updated cart info
                    $cartQuery = "SELECT c.id, c.quantita, p.tipologia as name, p.prezzo as price 
                                FROM carrello c 
                                JOIN prodotti p ON c.id_prodotto = p.id 
                                WHERE c.email_utente = '$email'";
                    $cartResult = mysqli_query($conn, $cartQuery);
                    
                    $cartItems = [];
                    $total = 0;
                    while ($row = mysqli_fetch_assoc($cartResult)) {
                        $cartItems[] = $row;
                        $total += $row['price'] * $row['quantita'];
                    }
                    
                    $countQuery = "SELECT SUM(quantita) as total FROM carrello WHERE email_utente = '$email'";
                    $countResult = mysqli_query($conn, $countQuery);
                    $cartTotal = mysqli_fetch_assoc($countResult)['total'] ?? 0;
                    
                    $response = [
                        'success' => true,
                        'cartItems' => $cartItems,
                        'cartTotal' => $cartTotal,
                        'total' => $total
                    ];
                }
            }
            break;
            
        case 'check_cart':
            $cartQuery = "SELECT COUNT(*) as count FROM carrello WHERE email_utente = '$email'";
            $result = mysqli_query($conn, $cartQuery);
            $cartCount = mysqli_fetch_assoc($result)['count'];
            
            $response = [
                'success' => true,
                'cartTotal' => $cartCount
            ];
            break;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Gestione verifica carrello via AJAX
if (isset($_POST['action']) && $_POST['action'] === 'check_cart' && $isLoggedIn) {
    $email = mysqli_real_escape_string($conn, $_SESSION['email']);
    $cartQuery = "SELECT COUNT(*) as count FROM carrello WHERE email_utente = '$email'";
    $result = mysqli_query($conn, $cartQuery);
    $cartCount = mysqli_fetch_assoc($result)['count'];
    
    echo json_encode([
        'success' => true,
        'cartTotal' => $cartCount
    ]);
    exit;
}

// Aggiunta della gestione sync_cart
if (isset($_POST['action']) && $_POST['action'] === 'sync_cart' && $isLoggedIn) {
    $email = mysqli_real_escape_string($conn, $_SESSION['email']);
    $items = json_decode($_POST['items'], true);
    
    // Svuota il carrello corrente
    mysqli_query($conn, "DELETE FROM carrello WHERE email_utente = '$email'");
    
    // Inserisci i nuovi prodotti
    $success = true;
    foreach ($items as $item) {
        $name = mysqli_real_escape_string($conn, $item['name']);
        $query = "SELECT id FROM prodotti WHERE tipologia = '$name' LIMIT 1";
        $result = mysqli_query($conn, $query);
        
        if ($row = mysqli_fetch_assoc($result)) {
            $productId = $row['id'];
            $success = mysqli_query($conn, "INSERT INTO carrello (email_utente, id_prodotto, quantita) VALUES ('$email', $productId, 1)");
            if (!$success) break;
        }
    }
    
    echo json_encode(['success' => $success]);
    exit;
}

// Ottieni il contenuto del carrello per l'utente loggato
$cartItems = [];
$cartTotal = 0;
if ($isLoggedIn && $conn) {
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

// Query per ottenere i prodotti dal database
$products = [];
if ($conn) {
    $query = "SELECT * FROM prodotti";
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = [
                'id' => $row['id'],
                'name' => $row['tipologia'],
                'price' => $row['prezzo'],
                'category' => $row['colore'],
                'image' => 'assets/products/' . $row['immagine'],
                'description' => $row['descrizione'] ?: 'Prodotto ' . $row['tipologia'] . ' di colore ' . $row['colore']
            ];
        }
    }
}

// Ottieni tutte le categorie uniche
$categories = array_unique(array_column($products, 'category'));

// Filtra i prodotti in base ai parametri di ricerca
$filteredProducts = $products;

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = strtolower($_GET['search']);
    $filteredProducts = array_filter($filteredProducts, function($product) use ($search) {
        return strpos(strtolower($product['name']), $search) !== false || 
               strpos(strtolower($product['description']), $search) !== false;
    });
}

if (isset($_GET['category']) && !empty($_GET['category'])) {
    $category = $_GET['category'];
    $filteredProducts = array_filter($filteredProducts, function($product) use ($category) {
        return $product['category'] === $category;
    });
}

$minPrice = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$maxPrice = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? floatval($_GET['max_price']) : 1000;

$filteredProducts = array_filter($filteredProducts, function($product) use ($minPrice, $maxPrice) {
    return $product['price'] >= $minPrice && $product['price'] <= $maxPrice;
});

// Gestisci l'ordinamento
if (isset($_GET['sort'])) {
    $sort = $_GET['sort'];
    switch ($sort) {
        case 'price_asc':
            usort($filteredProducts, function($a, $b) {
                return $a['price'] <=> $b['price'];
            });
            break;
        case 'price_desc':
            usort($filteredProducts, function($a, $b) {
                return $b['price'] <=> $a['price'];
            });
            break;
        case 'name_asc':
            usort($filteredProducts, function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
            break;
        case 'name_desc':
            usort($filteredProducts, function($a, $b) {
                return strcmp($b['name'], $a['name']);
            });
            break;
    }
}

if ($conn) {
    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - TorollerCollective</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap" rel="stylesheet">
    <?php $basePath = dirname($_SERVER['PHP_SELF']); if ($basePath == '/') $basePath = ''; ?>
    <link rel="stylesheet" href="<?php echo $basePath; ?>/style/header.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>/style/shop.css">
</head>
<body>
    <?php include 'components/header.php'; ?>
        
       
        
       
    </div>

    <div class="mobile-menu">
        <div class="close-menu">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </div>
        <a href="index.php" class="nav-link">Home</a>
        <a href="community.php" class="nav-link">Community</a>
        <a href="shop.php" class="nav-link active">Shop</a>
        <a href="eventi.php" class="nav-link">Eventi</a>
        
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

    <div class="shop-container">
        <!-- Mobile search bar sempre visibile su mobile -->
        <div class="mobile-search-container">
            <form action="shop.php" method="GET" class="mobile-search-form">
                <div class="search-box">
                    <input type="text" name="search" placeholder="Cerca prodotti..." class="search-input" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit" class="search-button">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                        </svg>
                    </button>
                </div>
            </form>
        </div>

        <button id="mobileFilterButton" class="mobile-filter-button">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="margin-right: 8px; vertical-align: text-bottom;">
                <path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5v-2z"/>
            </svg>
            Mostra filtri
        </button>
        
        <div id="filterSidebar" class="filter-sidebar">
            <form action="shop.php" method="GET">
                <div class="filter-card">
                    <h3 class="filter-title">Cerca</h3>
                    <div class="search-box inside-card">
                        <input type="text" name="search" placeholder="Cerca prodotti..." class="search-input" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <button type="submit" class="search-button">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="filter-card">
                    <h3 class="filter-title">Filtri</h3>
                    
                    <div class="filter-group">
                        <h4 class="filter-group-title">Categorie</h4>
                        <div class="category-list">
                            <?php foreach ($categories as $category): ?>
                            <div class="category-item">
                                <input type="radio" id="category-<?php echo htmlspecialchars($category); ?>" name="category" value="<?php echo htmlspecialchars($category); ?>" class="category-checkbox" <?php echo (isset($_GET['category']) && $_GET['category'] === $category) ? 'checked' : ''; ?>>
                                <label for="category-<?php echo htmlspecialchars($category); ?>" class="category-label"><?php echo htmlspecialchars($category); ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="filter-group">
                        <h4 class="filter-group-title">Prezzo</h4>
                        <div class="price-range">
                            <div class="price-inputs">
                                <input type="number" name="min_price" placeholder="Min" class="price-input" value="<?php echo isset($_GET['min_price']) ? htmlspecialchars($_GET['min_price']) : ''; ?>">
                                <span class="price-separator">-</span>
                                <input type="number" name="max_price" placeholder="Max" class="price-input" value="<?php echo isset($_GET['max_price']) ? htmlspecialchars($_GET['max_price']) : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="filter-group">
                        <h4 class="filter-group-title">Ordina per</h4>
                        <select name="sort" class="sort-dropdown">
                            <option value="name_asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'name_asc') ? 'selected' : ''; ?>>Nome (A-Z)</option>
                            <option value="name_desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'name_desc') ? 'selected' : ''; ?>>Nome (Z-A)</option>
                            <option value="price_asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'price_asc') ? 'selected' : ''; ?>>Prezzo (crescente)</option>
                            <option value="price_desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'price_desc') ? 'selected' : ''; ?>>Prezzo (decrescente)</option>
                        </select>
                    </div>
                    
                    <div class="buttons-container">
                        <button type="submit" class="filter-button">Applica filtri</button>
                        <button type="button" class="reset-button" onclick="window.location.href='shop.php'">Resetta filtri</button>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="products-section">
            <div class="products-header-simple">
                <h2 class="products-title">I nostri prodotti</h2>
                <p class="products-count"><?php echo count($filteredProducts); ?> prodotti trovati</p>
            </div>
            
            <?php if (count($filteredProducts) > 0): ?>
            <div class="products-grid">
                <?php foreach ($filteredProducts as $product): ?>
                <div class="product-card">
                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                    <div class="product-details">
                        <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="product-category"><?php echo htmlspecialchars($product['category']); ?></p>
                        <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                        <p class="product-price">€<?php echo number_format($product['price'], 2, ',', '.'); ?></p>
                        <?php if ($isLoggedIn): ?>
                            <form method="POST">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <button type="submit" name="add_to_cart" class="add-to-cart">Aggiungi al carrello</button>
                            </form>
                        <?php else: ?>
                            <a href="login.php" class="add-to-cart">Accedi per acquistare</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="no-products">
                <p>Nessun prodotto trovato. Prova a modificare i filtri di ricerca.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="footer">
        <div class="footer-content">
            <div class="footer-logo">
                <div class="footer-logo-text">TorollerCollective</div>
                <p class="footer-description">Una community di appassionati uniti dalla passione e dal desiderio di creare connessioni significative.</p>
            </div>
            
            <div class="footer-links">
                <div class="footer-column">
                    <div class="footer-column-title">Navigazione</div>
                    <a href="index.php" class="footer-link">Home</a>
                    <a href="community.php" class="footer-link">Community</a>
                    <a href="shop.php" class="footer-link">Shop</a>
                    <a href="/eventi.php" class="footer-link">Eventi</a>
                </div>
                
                <div class="footer-column">
                    <div class="footer-column-title">Account</div>
                    <?php if (!$isLoggedIn): ?>
                    <a href="login.php" class="footer-link">Accedi</a>
                    <a href="registrazione.php" class="footer-link">Registrati</a>
                    <?php else: ?>
                    <a href="#" class="footer-link">Il mio profilo</a>
                    <a href="shop.php?logout=1" class="footer-link">Logout</a>
                    <?php endif; ?>
                    <a href="#" class="footer-link">Assistenza</a>
                </div>
                
                <div class="footer-column">
                    <div class="footer-column-title">Contatti</div>
                    <a href="mailto:info@torollercollective.it" class="footer-link">info@torollercollective.it</a>
                    <a href="tel:+390123456789" class="footer-link">+39 0123 456789</a>
                    <a href="#" class="footer-link">Milano, Italia</a>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <div class="footer-copyright">© <?php echo date("Y"); ?> TorollerCollective. Tutti i diritti riservati.</div>
            
            <div class="footer-social">
            <a href="https://www.facebook.com/share/195xtDc71D/?mibextid=wwXIfr" class="footer-social-icon" target="_blank">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/>
                    </svg>
                </a>
                <a href="https://www.instagram.com/torollercollective?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw==" class="footer-social-icon"target="_blank">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z"/>
                    </svg>
                </a>
              
            </div>
        </div>
    </div>
    
    <script>
        // Pulisci il localStorage se l'utente non è loggato
        <?php if (!$isLoggedIn): ?>
        localStorage.removeItem('cart');
        <?php endif; ?>

        // Gestione menu mobile
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

            // Chiudi menu quando si clicca sui link
            mobileLinks.forEach(link => {
                link.addEventListener('click', toggleMenu);
            });

            // Toggle mobile filter sidebar
            document.getElementById('mobileFilterButton').addEventListener('click', function() {
                const filterSidebar = document.getElementById('filterSidebar');
                filterSidebar.classList.toggle('active');
                
                if (filterSidebar.classList.contains('active')) {
                    this.textContent = 'Nascondi filtri';
                } else {
                    this.textContent = 'Mostra filtri';
                    this.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="margin-right: 8px; vertical-align: text-bottom;"><path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5v-2z"/></svg> Mostra filtri';
                }
            });
        });

        // Carrello
        let cart = [];
        const cartBadge = document.getElementById('cartBadge');
        const cartItems = document.getElementById('cartItems');
        const cartTotal = document.getElementById('cartTotal');
        
        // Aggiornamento carrello
        function updateCart() {
            cartBadge.textContent = cart.length;
            cartItems.innerHTML = '';
            let total = 0;
            
            cart.forEach((item, index) => {
                const itemElement = document.createElement('div');
                itemElement.className = 'cart-item';
                itemElement.style.display = 'flex';
                itemElement.style.justifyContent = 'space-between';
                itemElement.style.alignItems = 'center';
                itemElement.style.padding = '8px 0';
                itemElement.style.borderBottom = '1px solid #E5E7EB';
                
                itemElement.innerHTML = `
                    <div>
                        <div style="font-weight: 600;">${item.name}</div>
                        <div style="color: #6B7280;">€${item.price.toFixed(2)}</div>
                    </div>
                    <button onclick="removeFromCart(${index})" style="color: #FF0000; background: none; border: none; cursor: pointer;">&times;</button>
                `;
                
                cartItems.appendChild(itemElement);
                total += item.price;
            });
            
            cartTotal.textContent = `€${total.toFixed(2)}`;
            
            // Salva il carrello nel localStorage
            localStorage.setItem('cart', JSON.stringify(cart));
        }
        
        // Rimuovi dal carrello
        function removeFromCart(index) {
            cart.splice(index, 1);
            updateCart();
        }
        
        // Carica il carrello dal localStorage
        window.addEventListener('load', () => {
            const savedCart = localStorage.getItem('cart');
            if (savedCart) {
                cart = JSON.parse(savedCart);
                updateCart();
            }
        });
        
        // Aggiungi al carrello (implementazione unica)
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function() {
                // Verifica se l'utente è loggato
                <?php if (!$isLoggedIn): ?>
                    window.location.href = 'login.php';
                    return;
                <?php endif; ?>

                const productCard = this.closest('.product-card');
                const name = productCard.querySelector('.product-name').textContent;
                const priceText = productCard.querySelector('.product-price').textContent;
                const price = parseFloat(priceText.replace('€', '').replace(',', '.'));
                
                cart.push({ name, price });
                updateCart();
                
                // Mostra feedback visivo
                this.textContent = 'Aggiunto!';
                setTimeout(() => {
                    this.textContent = 'Aggiungi al carrello';
                }, 1000);
            });
        });

        // Chiudi il popup del carrello quando si clicca fuori
        document.addEventListener('click', (e) => {
            const cartPopup = document.getElementById('cartPopup');
            const cartIcon = document.getElementById('cartIcon');
            
            if (!cartPopup.contains(e.target) && !cartIcon.contains(e.target)) {
                cartPopup.classList.remove('active');
            }
        });

        // Gestione click sull'icona del carrello
        const cartIcon = document.getElementById('cartIcon');
        const cartPopup = document.getElementById('cartPopup');
        
        cartIcon.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            <?php if (!$isLoggedIn): ?>
                window.location.href = 'login.php';
            <?php else: ?>
                cartPopup.classList.toggle('active');
            <?php endif; ?>
        });

        // Previene la chiusura quando si clicca all'interno del popup
        cartPopup.addEventListener('click', function(e) {
            e.stopPropagation();
        });

        // Funzione per il reindirizzamento al checkout
        function goToCheckout() {
            const cart = localStorage.getItem('cart');
            if (!cart || JSON.parse(cart).length === 0) {
                alert('Il tuo carrello è vuoto');
                return;
            }
            <?php if (!$isLoggedIn): ?>
            window.location.href = 'login.php';
            <?php else: ?>
            // Controlla se ci sono prodotti nel carrello
            fetch('shop.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=check_cart'
            })
            .then(response => response.json())
            .then(data => {
                if (data.cartTotal > 0) {
                    window.location.href = 'checkout.php';
                } else {
                    alert('Il tuo carrello è vuoto');
                }
            });
            <?php endif; ?>
        }

        // Gestione del carrello tramite AJAX
        function updateCartBadge(total) {
            document.getElementById('cartBadge').textContent = total;
        }

        function updateCartDisplay(cartItems, total) {
            const cartContainer = document.getElementById('cartItems');
            cartContainer.innerHTML = '';
            
            cartItems.forEach(item => {
                const itemElement = document.createElement('div');
                itemElement.className = 'cart-item';
                itemElement.style.display = 'flex';
                itemElement.style.justifyContent = 'space-between';
                itemElement.style.alignItems = 'center';
                itemElement.style.padding = '8px 0';
                itemElement.style.borderBottom = '1px solid #E5E7EB';
                
                itemElement.innerHTML = `
                    <div>
                        <div style="font-weight: 600;">${item.name}</div>
                        <div style="color: #6B7280;">€${item.price} x ${item.quantita}</div>
                    </div>
                    <button onclick="removeFromCart(${item.id})" style="color: #FF0000; background: none; border: none; cursor: pointer;">&times;</button>
                `;
                
                cartContainer.appendChild(itemElement);
            });
            
            document.getElementById('cartTotal').textContent = `€${total.toFixed(2)}`;
        }

        // Aggiungi al carrello
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function() {
                <?php if (!$isLoggedIn): ?>
                    window.location.href = 'login.php';
                    return;
                <?php endif; ?>

                const productCard = this.closest('.product-card');
                const productId = productCard.dataset.productId;
                
                fetch('shop.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=add_to_cart&product_id=${productId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateCartBadge(data.cartTotal);
                        this.textContent = 'Aggiunto!';
                        setTimeout(() => {
                            this.textContent = 'Aggiungi al carrello';
                        }, 1000);
                    }
                });
            });
        });

        // Rimuovi dal carrello
        function removeFromCart(cartItemId) {
            fetch('shop.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=remove_from_cart&cart_item_id=${cartItemId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCartBadge(data.cartTotal);
                    updateCartDisplay(data.cartItems, data.total);
                }
            });
        }

        // Gestione del popup del carrello
        document.addEventListener('click', (e) => {
            const cartPopup = document.getElementById('cartPopup');
            const cartIcon = document.getElementById('cartIcon');
            
            if (!cartPopup.contains(e.target) && !cartIcon.contains(e.target)) {
                cartPopup.classList.remove('active');
            }
        });

        cartIcon.addEventListener('click', function(e) {
            e.stopPropagation();
            cartPopup.classList.toggle('active');
        });

        cartPopup.addEventListener('click', function(e) {
            e.stopPropagation();
        });

        // Funzione per il checkout
        function goToCheckout() {
            <?php if (!$isLoggedIn): ?>
                window.location.href = 'login.php';
            <?php else: ?>
                // Controlla se ci sono prodotti nel carrello
                fetch('shop.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=check_cart'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.cartTotal > 0) {
                        window.location.href = 'checkout.php';
                    } else {
                        alert('Il tuo carrello è vuoto');
                    }
                });
            <?php endif; ?>
        }

        // Modifica alla funzione JavaScript goToCheckout
        function goToCheckout() {
            <?php if (!$isLoggedIn): ?>
                window.location.href = 'login.php';
                return;
            <?php endif; ?>
            
            // Sincronizza il carrello client con il server
            const cartItems = JSON.parse(localStorage.getItem('cart') || '[]');
            if (cartItems.length === 0) {
                alert('Il tuo carrello è vuoto');
                return;
            }
            
            // Invia i prodotti al server e poi vai al checkout
            fetch('shop.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'sync_cart',
                    items: JSON.stringify(cartItems)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'checkout.php';
                } else {
                    alert('Si è verificato un errore. Riprova.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Si è verificato un errore. Riprova.');
            });
        }

        // Inizializza il badge del carrello
        updateCartBadge(<?php echo array_sum(array_column($cartItems, 'quantita')) ?? 0; ?>);

        function toggleCart(event) {
            if (event) {
                event.preventDefault();
            }
            <?php if (!$isLoggedIn): ?>
                window.location.href = 'login.php';
                return;
            <?php endif; ?>
            const cartPopup = document.getElementById('cartPopup');
            cartPopup.classList.toggle('active');
        }

        // Gestione dei filtri mobile
        document.getElementById('mobileFilterButton').addEventListener('click', function() {
            const filterSidebar = document.getElementById('filterSidebar');
            filterSidebar.classList.toggle('active');
            
            // Aggiorna il testo del pulsante
            if (filterSidebar.classList.contains('active')) {
                this.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="margin-right: 8px; vertical-align: text-bottom;"><path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/></svg>Nascondi filtri';
            } else {
                this.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="margin-right: 8px; vertical-align: text-bottom;"><path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5v-2z"/></svg>Mostra filtri';
            }
        });

        // Chiudi il carrello quando si clicca fuori
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
    </script>
</body>
</html>
