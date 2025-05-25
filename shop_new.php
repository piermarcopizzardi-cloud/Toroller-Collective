<?php
session_start();
include("conn.php");
include("includes/utility.php");
include("includes/auth.php");
include("includes/database.php");

// Controllo se l'utente è loggato
if (!isUserLoggedIn()) {
    redirectWithMessage('login.php', 'È necessario accedere per visualizzare questa pagina', 'error');
}

$conn = null;
try {
    $conn = connetti("toroller_semplificato");
    if (!$conn) {
        throw new Exception("Errore di connessione al database");
    }
} catch (Exception $e) {
    error_log("Errore database: " . $e->getMessage());
}

// Ottieni informazioni utente
$userEmail = '';
$userName = '';
if ($conn && isset($_SESSION['email'])) {
    $userData = getUserData($conn, $_SESSION['email']);
    if ($userData) {
        $userEmail = $userData['email'];
        $userName = $userData['username'];
    } else {
        // Utente non trovato nel DB nonostante la sessione esista
        error_log("User session exists for email: " . $_SESSION['email'] . " but user not found in DB.");
    }
}

// Prepara i filtri per la ricerca
$filters = [
    'search_term' => isset($_GET['search_term']) ? sanitizeInput($_GET['search_term']) : '',
    'category' => isset($_GET['category']) ? sanitizeInput($_GET['category']) : 'all'
];

// Recupera i servizi filtrati
$servizi = [];
if ($conn) {
    $servizi = getAllServices($conn, $filters);
    $categorie = getAllServiceCategories($conn);
    mysqli_close($conn);
}

// Definisci il percorso base per i file CSS e JS
$basePath = getBasePath();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servizi - TorollerCollective</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap" rel="stylesheet">
    <meta name="base-path" content="<?php echo $basePath; ?>">
    <link href="<?php echo $basePath; ?>/style/header.css" rel="stylesheet">
    <link href="<?php echo $basePath; ?>/style/shop.css" rel="stylesheet">
</head>
<body>
    <?php include 'components/header.php'; ?>

    <div class="main-content">
        <div class="shop-header">
            <h1 class="shop-title">I Nostri Servizi</h1>
            <p class="shop-subtitle">Esplora la gamma di servizi che offriamo.</p>
        </div>

        <!-- Primo passo: Ricerca -->
        <div class="search-filter-section">
            <form method="GET" action="shop.php" id="searchForm">
                <input type="text" name="search_term" placeholder="Cerca servizio per nome..." 
                       value="<?php echo htmlspecialchars($filters['search_term']); ?>">
                <select name="category">
                    <option value="all"<?php echo ($filters['category'] === 'all') ? ' selected' : ''; ?>>Tutte le categorie</option>
                    <?php if (isset($categorie) && is_array($categorie)): ?>
                        <?php foreach ($categorie as $categoria): ?>
                            <option value="<?php echo htmlspecialchars($categoria); ?>"
                                    <?php echo ($filters['category'] === $categoria) ? ' selected' : ''; ?>>
                                <?php echo htmlspecialchars($categoria); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <button type="submit">Cerca</button>
            </form>
        </div>

        <div class="products-grid">
            <?php if (empty($servizi)): ?>
                <p class="no-results">Nessun servizio trovato. Prova a cambiare i filtri di ricerca.</p>
            <?php else: ?>
                <?php foreach ($servizi as $servizio): ?>
                    <div class="product-card">
                        <div class="product-info">
                            <h3 class="product-name"><?php echo htmlspecialchars($servizio['nome']); ?></h3>
                            <p class="product-category">Categoria: <?php echo htmlspecialchars($servizio['categoria']); ?></p>
                            <p class="product-description"><?php echo htmlspecialchars($servizio['descrizione']); ?></p>
                            <div class="product-actions">
                                <a href="servizio_dettaglio.php?id=<?php echo $servizio['id']; ?>" class="view-details-btn">
                                    Visualizza dettagli
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // JavaScript per la pagina shop
            console.log('Shop page loaded');
            
            // Event listener per il form di ricerca
            const searchForm = document.getElementById('searchForm');
            if (searchForm) {
                searchForm.addEventListener('reset', function() {
                    setTimeout(() => {
                        this.submit();
                    }, 10);
                });
            }
        });
    </script>
</body>
</html>
