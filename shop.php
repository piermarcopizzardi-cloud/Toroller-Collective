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

// Ottieni le informazioni dell'utente se è loggato
$userEmail = '';
$userName = '';
if ($isLoggedIn) {
    $conn = connetti("toroller");
    $email = mysqli_real_escape_string($conn, $_SESSION['email']);
    $query = "SELECT nome, email FROM utente WHERE email = '$email'";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $userEmail = $user['email'];
        $userName = $user['nome'];
    }
    mysqli_close($conn);
}

// Sample product data (in a real application, this would come from a database)
$products = [
    [
        'id' => 1,
        'name' => 'Toroller Classic',
        'price' => 49.99,
        'category' => 'Accessori',
        'image' => 'assets/product1.jpg',
        'description' => 'Il nostro prodotto classico, perfetto per ogni occasione.'
    ],
    [
        'id' => 2,
        'name' => 'Toroller Pro',
        'price' => 79.99,
        'category' => 'Accessori',
        'image' => 'assets/product2.jpg',
        'description' => 'Versione professionale con caratteristiche avanzate.'
    ],
    [
        'id' => 3,
        'name' => 'T-Shirt Toroller',
        'price' => 24.99,
        'category' => 'Abbigliamento',
        'image' => 'assets/product3.jpg',
        'description' => 'T-shirt in cotone 100% con logo Toroller.'
    ],
    [
        'id' => 4,
        'name' => 'Cappellino Toroller',
        'price' => 19.99,
        'category' => 'Abbigliamento',
        'image' => 'assets/product4.jpg',
        'description' => 'Cappellino regolabile con logo ricamato.'
    ],
    [
        'id' => 5,
        'name' => 'Toroller Mini',
        'price' => 34.99,
        'category' => 'Accessori',
        'image' => 'assets/product5.jpg',
        'description' => 'Versione compatta del nostro prodotto principale.'
    ],
    [
        'id' => 6,
        'name' => 'Felpa Toroller',
        'price' => 59.99,
        'category' => 'Abbigliamento',
        'image' => 'assets/product6.jpg',
        'description' => 'Felpa calda e confortevole con logo Toroller.'
    ],
    [
        'id' => 7,
        'name' => 'Toroller Limited Edition',
        'price' => 99.99,
        'category' => 'Edizioni Limitate',
        'image' => 'assets/product7.jpg',
        'description' => 'Edizione limitata con design esclusivo.'
    ],
    [
        'id' => 8,
        'name' => 'Borsa Toroller',
        'price' => 39.99,
        'category' => 'Accessori',
        'image' => 'assets/product8.jpg',
        'description' => 'Borsa in tela resistente con logo Toroller.'
    ]
];

// Get all unique categories
$categories = array_unique(array_column($products, 'category'));

// Filter products based on search and filter parameters
$filteredProducts = $products;

// Handle search
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = strtolower($_GET['search']);
    $filteredProducts = array_filter($filteredProducts, function($product) use ($search) {
        return strpos(strtolower($product['name']), $search) !== false || 
               strpos(strtolower($product['description']), $search) !== false;
    });
}

// Handle category filter
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $category = $_GET['category'];
    $filteredProducts = array_filter($filteredProducts, function($product) use ($category) {
        return $product['category'] === $category;
    });
}

// Handle price range filter
$minPrice = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$maxPrice = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? floatval($_GET['max_price']) : 1000;

$filteredProducts = array_filter($filteredProducts, function($product) use ($minPrice, $maxPrice) {
    return $product['price'] >= $minPrice && $product['price'] <= $maxPrice;
});

// Handle sorting
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

// Handle logout
if (isset($_GET['logout'])) {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
    
    // Redirect to the homepage
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - TorollerCollective</title>
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
            font-family: 'Inter', sans-serif;
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
        
        .nav-link-with-icon {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .auth-buttons {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .login-btn {
            color: #BDD3C6;
            font-size: 16px;
            padding: 18px 24px;
            border: 1px solid #7FE47E;
            border-radius: 30px;
            text-decoration: none;
        }
        
        .get-started-btn {
            color: #ffffff;
            font-size: 16px;
            font-weight: 700;
            padding: 18px 24px;
            background-color: #04CD00;
            border-radius: 30px;
            text-decoration: none;
            border: none;
            cursor: pointer;
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
        
        .logout-btn:hover {
            color: #04CD00;
        }

        .events-hero {
            background-color: #04CD00;
            color: white;
            padding: 80px 110px;
            text-align: center;
        }

        .events-hero-title {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 24px;
        }

        .events-hero-description {
            font-size: 20px;
            max-width: 800px;
            margin: 0 auto;
            line-height: 1.5;
        }

        .events-container {
            padding: 80px 110px;
            background-color: #F9FAFB;
        }

        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .event-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }

        .event-card:hover {
            transform: translateY(-5px);
        }

        .event-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .event-content {
            padding: 24px;
        }

        .event-date {
            color: #04CD00;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .event-title {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 12px;
        }

        .event-location {
            color: #6B7280;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .event-description {
            color: #4B5563;
            margin-bottom: 24px;
            line-height: 1.5;
        }

        .event-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 16px;
            border-top: 1px solid #E5E7EB;
        }

        .event-time {
            color: #6B7280;
            font-size: 14px;
        }

        .event-participants {
            color: #04CD00;
            font-size: 14px;
            font-weight: 600;
        }

        .join-event-btn {
            display: inline-block;
            background-color: #04CD00;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
        }

        .join-event-btn:hover {
            background-color: #03b600;
        }

        .hamburger-menu {
            display: none;
        }
      
        .shop-container {
            padding: 40px 110px;
            display: flex;
            gap: 40px;
        }
        
        .filter-sidebar {
            width: 300px;
            flex-shrink: 0;
        }
        
        .filter-card {
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .filter-title {
            color: #04CD00;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 16px;
        }
        
        .search-box {
            display: flex;
            margin-bottom: 16px;
        }
        
        .search-input {
            flex-grow: 1;
            padding: 12px 16px;
            border: 1px solid #E5E7EB;
            border-radius: 8px 0 0 8px;
            font-size: 14px;
        }
        
        .search-button {
            background-color: #04CD00;
            color: white;
            border: none;
            border-radius: 0 8px 8px 0;
            padding: 0 16px;
            cursor: pointer;
        }
        
        .filter-group {
            margin-bottom: 20px;
        }
        
        .filter-group-title {
            color: #333;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 12px;
        }
        
        .category-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .category-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .category-checkbox {
            width: 18px;
            height: 18px;
            accent-color: #04CD00;
        }
        
        .category-label {
            color: #4B5563;
            font-size: 14px;
        }
        
        .price-range {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .price-inputs {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .price-input {
            width: 100px;
            padding: 8px 12px;
            border: 1px solid #E5E7EB;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .price-separator {
            color: #6B7280;
        }
        
        .filter-button {
            width: 100%;
            padding: 12px;
            background-color: #04CD00;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 16px;
        }
        
        .reset-button {
            width: 100%;
            padding: 12px;
            background-color: transparent;
            color: #6B7280;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 8px;
        }
        
        .products-section {
            flex-grow: 1;
        }
        
        .products-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        
        .products-title {
            color: #04CD00;
            font-size: 28px;
            font-weight: 700;
        }
        
        .products-count {
            color: #6B7280;
            font-size: 16px;
        }
        
        .sort-dropdown {
            padding: 10px 16px;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            font-size: 14px;
            color: #4B5563;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }
        
        .product-card {
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
        }
        
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .product-details {
            padding: 20px;
        }
        
        .product-name {
            color: #333;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .product-category {
            color: #6B7280;
            font-size: 14px;
            margin-bottom: 12px;
        }
        
        .product-description {
            color: #4B5563;
            font-size: 14px;
            margin-bottom: 16px;
            line-height: 1.4;
        }
        
        .product-price {
            color: #04CD00;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 16px;
        }
        
        .add-to-cart {
            width: 100%;
            padding: 12px;
            background-color: #04CD00;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }
        
        .no-products {
            text-align: center;
            padding: 40px;
            color: #6B7280;
            font-size: 18px;
        }
        
        .mobile-filter-button {
            display: none;
            width: 100%;
            padding: 12px;
            background-color: #04CD00;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            margin-bottom: 16px;
        }
        
        .footer {
            padding: 60px 110px 30px;
            background-color: #1F2937;
            color: #ffffff;
        }
        
        .footer-content {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }
        
        .footer-logo {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .footer-logo-text {
            color: #04CD00;
            font-size: 24px;
            font-weight: 800;
        }
        
        .footer-description {
            color: #D1D5DB;
            max-width: 300px;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .footer-links {
            display: flex;
            gap: 80px;
        }
        
        .footer-column {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .footer-column-title {
            color: #ffffff;
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .footer-link {
            color: #D1D5DB;
            font-size: 14px;
            text-decoration: none;
        }
        
        .footer-link:hover {
            color: #04CD00;
        }
        
        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 30px;
            border-top: 1px solid #374151;
        }
        
        .footer-copyright {
            color: #D1D5DB;
            font-size: 14px;
        }
        
        .footer-social {
            display: flex;
            gap: 16px;
        }
        
        .footer-social-icon {
            width: 36px;
            height: 36px;
            background-color: #374151;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .footer-social-icon svg {
            width: 20px;
            height: 20px;
            color: #ffffff;
        }
        
        @media (max-width: 1200px) {
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 991px) {
            .header {
                padding-left: 40px;
                padding-right: 40px;
            }
            
            .shop-container {
                padding: 40px;
                flex-direction: column;
            }
            
            .filter-sidebar {
                width: 100%;
                display: none;
            }
            
            .filter-sidebar.active {
                display: block;
            }
            
            .mobile-filter-button {
                display: block;
            }
            
            .footer {
                padding-left: 40px;
                padding-right: 40px;
            }
            
            .footer-content {
                flex-direction: column;
                gap: 40px;
            }
            
            .footer-links {
                flex-wrap: wrap;
                gap: 40px;
            }
        }
        
        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: 1fr;
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
            }
            
            .shop-container {
                padding: 20px;
            }
            
            .products-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
            
            .footer {
                padding-left: 20px;
                padding-right: 20px;
            }
            
            .footer-bottom {
                flex-direction: column;
                gap: 20px;
            }
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
                    <div>
                        <svg width="12" height="12" viewBox="0 0 66 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <text fill="#BDD3C6" xml:space="preserve" style="white-space: pre" font-family="DM Sans" font-size="18" letter-spacing="0px"><tspan x="0.475952" y="15.2126">Shop</tspan></text>
                            <path d="M53.3334 6.15796L59.1667 11.9913L65 6.15796" stroke="#211F54" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </div>
                </div>
                <a class="nav-link " href="eventi.php">Eventi</a>
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
    
    <div class="shop-container">
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
                    <div class="search-box">
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
                    
                    <button type="submit" class="filter-button">Applica filtri</button>
                    <a href="shop.php" class="reset-button">Resetta filtri</a>
                </div>
            </form>
        </div>
        
        <div class="products-section">
            <div class="products-header">
                <div>
                    <h2 class="products-title">I nostri prodotti</h2>
                    <p class="products-count"><?php echo count($filteredProducts); ?> prodotti trovati</p>
                </div>
                
                <select id="mobileSortDropdown" class="sort-dropdown" onchange="window.location.href=this.value">
                    <option value="shop.php?sort=name_asc<?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['category']) ? '&category=' . urlencode($_GET['category']) : ''; ?><?php echo isset($_GET['min_price']) ? '&min_price=' . urlencode($_GET['min_price']) : ''; ?><?php echo isset($_GET['max_price']) ? '&max_price=' . urlencode($_GET['max_price']) : ''; ?>" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'name_asc') ? 'selected' : ''; ?>>Nome (A-Z)</option>
                    <option value="shop.php?sort=name_desc<?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['category']) ? '&category=' . urlencode($_GET['category']) : ''; ?><?php echo isset($_GET['min_price']) ? '&min_price=' . urlencode($_GET['min_price']) : ''; ?><?php echo isset($_GET['max_price']) ? '&max_price=' . urlencode($_GET['max_price']) : ''; ?>" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'name_desc') ? 'selected' : ''; ?>>Nome (Z-A)</option>
                    <option value="shop.php?sort=price_asc<?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['category']) ? '&category=' . urlencode($_GET['category']) : ''; ?><?php echo isset($_GET['min_price']) ? '&min_price=' . urlencode($_GET['min_price']) : ''; ?><?php echo isset($_GET['max_price']) ? '&max_price=' . urlencode($_GET['max_price']) : ''; ?>" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'price_asc') ? 'selected' : ''; ?>>Prezzo (crescente)</option>
                    <option value="shop.php?sort=price_desc<?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['category']) ? '&category=' . urlencode($_GET['category']) : ''; ?><?php echo isset($_GET['min_price']) ? '&min_price=' . urlencode($_GET['min_price']) : ''; ?><?php echo isset($_GET['max_price']) ? '&max_price=' . urlencode($_GET['max_price']) : ''; ?>" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'price_desc') ? 'selected' : ''; ?>>Prezzo (decrescente)</option>
                </select>
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
                        <button class="add-to-cart">Aggiungi al carrello</button>
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
        
        // Add to cart functionality (placeholder)
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function() {
                alert('Prodotto aggiunto al carrello!');
            });
        });
    </script>
</body>
</html>
