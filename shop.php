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

if ($isLoggedIn && isset($_GET['action'])) {
    $conn = connetti("toroller"); 
    $email = mysqli_real_escape_string($conn, $_SESSION['email']);
    header('Content-Type: application/json'); // Set content type for all AJAX responses here
    $response = ['success' => false, 'message' => '', 'cartItems' => [], 'total' => 0, 'cartTotalQuantity' => 0];

    if ($_GET['action'] === 'get_cart_items') {
        $cartQuery = "SELECT c.id, c.quantita, p.tipologia as name, p.prezzo as price, p.id as product_id, p.immagine as image \n                     FROM carrello c \n                     JOIN prodotti p ON c.id_prodotto = p.id \n                     WHERE c.email_utente = '$email'";
        $cartResult = mysqli_query($conn, $cartQuery);
        $currentCartItems = [];
        $currentTotal = 0;
        $currentTotalQuantity = 0;
        if ($cartResult) {
            while ($row = mysqli_fetch_assoc($cartResult)) {
                if (!empty($row['image'])) {
                    $row['image'] = 'assets/products/' . $row['image'];
                } else {
                    $row['image'] = null; // Ensure JS placeholder logic works
                }
                $currentCartItems[] = $row;
                $currentTotal += $row['price'] * $row['quantita'];
                $currentTotalQuantity += $row['quantita'];
            }
            $response['success'] = true;
            $response['cartItems'] = $currentCartItems;
            $response['total'] = $currentTotal;
            $response['cartTotalQuantity'] = $currentTotalQuantity;
        } else {
            $response['message'] = 'Errore nel recupero degli articoli del carrello: ' . mysqli_error($conn);
        }
        echo json_encode($response);
        exit;
    }

    if ($_GET['action'] === 'get_cart_quantity') {
        $cartQuery = "SELECT SUM(quantita) as total_quantity FROM carrello WHERE email_utente = '$email'";
        $cartResult = mysqli_query($conn, $cartQuery);
        if ($cartResult) {
            $row = mysqli_fetch_assoc($cartResult);
            $response['success'] = true;
            $response['cartTotalQuantity'] = $row['total_quantity'] ? (int)$row['total_quantity'] : 0;
        } else {
            $response['message'] = 'Errore nel recuperare la quantità del carrello: ' . mysqli_error($conn);
        }
        echo json_encode($response);
        exit;
    }
    // Potrebbero esserci altre azioni GET qui, altrimenti chiudi la connessione se necessario
    if ($conn) mysqli_close($conn);
}

// Ajax cart handling for POST requests
if (isset($_POST['action']) && $isLoggedIn) {
    if (!$conn || $conn->connect_error) { // Ensure connection is still valid or reconnect
        $conn = connetti("toroller");
    }
    $email = mysqli_real_escape_string($conn, $_SESSION['email']);
    header('Content-Type: application/json'); // Ensure content type is JSON
    $response = ['success' => false, 'message' => '', 'cartTotalQuantity' => 0 ];

    switch ($_POST['action']) {
        case 'add_to_cart':
            if (isset($_POST['product_id'])) {
                $productId = (int)$_POST['product_id'];
                $query = "SELECT id, quantita FROM carrello WHERE email_utente = ? AND id_prodotto = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "si", $email, $productId);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if (mysqli_num_rows($result) > 0) {
                    $row = mysqli_fetch_assoc($result);
                    $newQuantity = $row['quantita'] + 1;
                    $updateQuery = "UPDATE carrello SET quantita = ? WHERE id = ?";
                    $stmtUpdate = mysqli_prepare($conn, $updateQuery);
                    mysqli_stmt_bind_param($stmtUpdate, "ii", $newQuantity, $row['id']);
                    $success = mysqli_stmt_execute($stmtUpdate);
                } else {
                    $insertQuery = "INSERT INTO carrello (email_utente, id_prodotto, quantita) VALUES (?, ?, 1)";
                    $stmtInsert = mysqli_prepare($conn, $insertQuery);
                    mysqli_stmt_bind_param($stmtInsert, "si", $email, $productId);
                    $success = mysqli_stmt_execute($stmtInsert);
                }
                
                if ($success) {
                    $response['success'] = true;
                    $response['message'] = 'Prodotto aggiunto al carrello!';
                } else {
                    $response['message'] = 'Errore nell\'aggiunta al carrello: ' . mysqli_error($conn);
                }
            }
            break;
            
        case 'remove_from_cart':
            if (isset($_POST['cart_item_id'])) {
                $cartItemId = (int)$_POST['cart_item_id'];
                $deleteQuery = "DELETE FROM carrello WHERE id = ? AND email_utente = ?";
                $stmt = mysqli_prepare($conn, $deleteQuery);
                mysqli_stmt_bind_param($stmt, "is", $cartItemId, $email);
                $success = mysqli_stmt_execute($stmt);
                                
                if ($success) {
                    $response['success'] = true;
                    $response['message'] = 'Articolo rimosso dal carrello.';
                } else {
                    $response['message'] = 'Errore nella rimozione dell\'articolo: ' . mysqli_error($conn);
                }
            }
            break;
    }

    // After add/remove, update total quantity for the badge for all POST actions
    if ($response['success']) {
        $countQuery = "SELECT SUM(quantita) as total_quantity FROM carrello WHERE email_utente = ?";
        $stmtCount = mysqli_prepare($conn, $countQuery);
        mysqli_stmt_bind_param($stmtCount, "s", $email);
        mysqli_stmt_execute($stmtCount);
        $countResult = mysqli_stmt_get_result($stmtCount);
        if ($countRow = mysqli_fetch_assoc($countResult)) {
            $response['cartTotalQuantity'] = $countRow['total_quantity'] ? (int)$countRow['total_quantity'] : 0;
        }
    }
    
    echo json_encode($response);
    if ($conn) mysqli_close($conn);
    exit();
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
    <meta name="base-path" content="<?php echo rtrim(dirname($_SERVER['PHP_SELF']), '/'); ?>">
    <title>Shop - TorollerCollective</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap" rel="stylesheet">
    <?php $basePath = dirname($_SERVER['PHP_SELF']); if ($basePath == '/') $basePath = ''; ?>
    <link rel="stylesheet" href="<?php echo $basePath; ?>/style/header.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>/style/cart.css"> <script>//NOTE: cart.css should be linked in all pages not just shop.php</script>
    <link rel="stylesheet" href="<?php echo $basePath; ?>/style/shop.css">
</head>
<body>
    <?php include 'components/header.php'; ?>
    
    <div class="shop-container">
        <aside class="filter-sidebar" id="filterSidebar">
            <!-- Search Form -->
            <div class="filter-card">
                <h3 class="filter-title">Cerca Prodotti</h3>
                <form method="GET" action="shop.php" class="search-box inside-card">
                    <input type="text" name="search" class="search-input" placeholder="Cerca..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit" class="search-button">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                        </svg>
                    </button>
                </form>
            </div>

            <!-- Filter Form -->
            <form method="GET" action="shop.php" id="filterForm">
                <input type="hidden" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <?php // Rimosso l'input nascosto per "sort" da qui, dato che il select ora è parte di questo form ?>

                <div class="filter-card">
                    <h3 class="filter-title">Filtri</h3> <?php // Titolo generico per la card dei filtri ?>
                    
                    <div class="filter-group">
                        <label for="sort" class="filter-group-title">Ordina per:</label>
                        <select name="sort" id="sort" class="sort-dropdown" onchange="document.getElementById('filterForm').submit()">
                            <option value="" <?php echo !isset($_GET['sort']) ? 'selected' : ''; ?>>Predefinito</option>
                            <option value="price_asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'price_asc') ? 'selected' : ''; ?>>Prezzo: Crescente</option>
                            <option value="price_desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'price_desc') ? 'selected' : ''; ?>>Prezzo: Decrescente</option>
                            <option value="name_asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'name_asc') ? 'selected' : ''; ?>>Nome: A-Z</option>
                            <option value="name_desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'name_desc') ? 'selected' : ''; ?>>Nome: Z-A</option>
                        </select>
                    </div>

                    <h4 class="filter-group-title">Categorie</h4>
                    <div class="filter-group category-list">
                        <?php foreach ($categories as $cat): ?>
                            <label class="category-item">
                                <input type="checkbox" name="category[]" value="<?php echo htmlspecialchars($cat); ?>" 
                                       class="category-checkbox" <?php echo (isset($_GET['category']) && in_array($cat, (array)$_GET['category'])) ? 'checked' : ''; ?>>
                                <span class="category-label"><?php echo htmlspecialchars(ucfirst($cat)); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="filter-card">
                    <h3 class="filter-title">Prezzo</h3>
                    <div class="filter-group price-range">
                        <div class="price-inputs">
                            <input type="number" name="min_price" class="price-input" placeholder="Min" value="<?php echo isset($_GET['min_price']) ? htmlspecialchars($_GET['min_price']) : ''; ?>">
                            <span class="price-separator">-</span>
                            <input type="number" name="max_price" class="price-input" placeholder="Max" value="<?php echo isset($_GET['max_price']) ? htmlspecialchars($_GET['max_price']) : ''; ?>">
                        </div>
                    </div>
                </div>
                
                <div class="filter-card buttons-container">
                    <button type="submit" class="filter-button">Applica Filtri</button>
                    <button type="button" class="reset-button" onclick="window.location.href='shop.php'">Resetta Filtri</button>
                </div>
            </form>
        </aside>

        <main class="products-section">
            <div class="products-header-simple">
                <h2 class="products-title">I nostri Prodotti</h2>
                <p class="products-count"><?php echo count($filteredProducts); ?> prodotti trovati</p>
            </div>
            
            <?php // Rimosso il vecchio div "sort-options" e il form "sortForm" ?>

            <?php if (empty($filteredProducts)): ?>
                <p class="no-products">Nessun prodotto trovato che corrisponda ai tuoi criteri di ricerca.</p>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($filteredProducts as $product): ?>
                        <div class="product-card" data-product-id="<?php echo $product['id']; ?>">
                            <img src="<?php echo $basePath . '/' . $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                            <div class="product-details">
                                <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="product-category"><?php echo htmlspecialchars(ucfirst($product['category'])); ?></p>
                                <!-- <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p> -->
                                <p class="product-price">€<?php echo number_format($product['price'], 2, ',', '.'); ?></p>
                                <button class="add-to-cart">Aggiungi al Carrello</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <button id="mobileFilterButton" class="mobile-filter-button">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="margin-right: 8px; vertical-align: text-bottom;">
            <path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5v-2z"/>
        </svg>
        Mostra filtri
    </button>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add to cart functionality
        const addToCartButtons = document.querySelectorAll('.add-to-cart');
        addToCartButtons.forEach(button => {
            button.addEventListener('click', function() {
                <?php if (!$isLoggedIn): ?>
                    window.location.href = '<?php echo $basePath; ?>/login.php';
                    return;
                <?php endif; ?>

                const productCard = this.closest('.product-card');
                const productId = productCard.dataset.productId;
                
                const formData = new FormData();
                formData.append('action', 'add_to_cart');
                formData.append('product_id', productId);

                fetch('<?php echo $basePath; ?>/shop.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // alert('Prodotto aggiunto al carrello!'); // Optional: give user feedback
                        if (window.refreshCartState) {
                            window.refreshCartState(false); // Update badge, don't show popup
                        }
                    } else {
                        alert(data.message || 'Errore nell\'aggiunta al carrello.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Si è verificato un errore. Riprova.');
                });
            });
        });

        // Go to checkout (example, if you have a direct checkout button on shop page)
        // This specific function might not be needed if checkout is only from popup
        function goToCheckout() {
            <?php if (!$isLoggedIn): ?>
                window.location.href = 'login.php';
                return;
            <?php endif; ?>

            fetch('<?php echo $basePath; ?>/shop.php?action=get_cart_quantity') 
            .then(response => response.json())
            .then(data => {
                if (data.success && data.cartTotalQuantity > 0) {
                    window.location.href = '<?php echo $basePath; ?>/checkout.php';
                } else if (data.success && data.cartTotalQuantity === 0) {
                    alert('Il tuo carrello è vuoto.');
                } else {
                    alert(data.message || 'Errore nel controllare il carrello.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Si è verificato un errore. Riprova.');
            });
        }

        // Mobile filter button functionality
        const mobileFilterButton = document.getElementById('mobileFilterButton');
        const filterSidebar = document.getElementById('filterSidebar');

        if (mobileFilterButton && filterSidebar) {
            mobileFilterButton.addEventListener('click', function() {
                filterSidebar.classList.toggle('active');
                if (filterSidebar.classList.contains('active')) {
                    this.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="margin-right: 8px; vertical-align: text-bottom;"><path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/></svg>Nascondi filtri';
                } else {
                    this.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="margin-right: 8px; vertical-align: text-bottom;"><path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5v-2z"/></svg>Mostra filtri';
                }
            });
        }
    });
    </script>
    <script src="<?php echo $basePath; ?>/components/header.js"></script>
</body>
</html>
