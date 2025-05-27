<?php
session_start();

// Controllo se l'utente è loggato
if (!isset($_SESSION['username'])) {
    // Se non è loggato, reindirizza alla pagina di login
    header('Location: login.php');
    exit(); // Termina lo script per assicurarsi che il reindirizzamento avvenga
}

include("conn.php");

// La variabile $isLoggedIn può essere basata direttamente su $_SESSION['username']
// dato che il controllo di accesso principale all'inizio del file usa questa variabile.
// Se lo script arriva qui, $_SESSION['username'] è necessariamente settato.
$isLoggedIn = isset($_SESSION['username']); // Semplificato e allineato

// Se l'utente ha cliccato su logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

$conn = null;
try {
    $conn = connetti("toroller_semplificato"); // Corrected DB name
    if (!$conn) {
        throw new Exception("Errore di connessione al database");
    }
} catch (Exception $e) {
    error_log("Errore database: " . $e->getMessage());
    // Considerare di mostrare un messaggio all'utente o gestire l'errore in modo più visibile se $conn rimane null
}

// Ottieni le informazioni dell'utente se è loggato
$userEmail = '';
$userName = '';
// Dato che il controllo if (!isset($_SESSION['username'])) all'inizio del file avrebbe causato un exit()
// se l'utente non fosse loggato, possiamo assumere che $_SESSION['username'] sia settato qui.
if ($conn && isset($_SESSION['username'])) { // Aggiunto controllo esplicito per $_SESSION['username'] per chiarezza
    $current_username = mysqli_real_escape_string($conn, $_SESSION['username']);
    $query = "SELECT username, nome, email FROM utente WHERE username = '$current_username'";
    
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $userEmail = $user['email']; 
        $userName = $user['username']; 
    } else {
        // Utente non trovato nel DB nonostante la sessione esista?
        // Potrebbe essere un caso limite da loggare o gestire (es. distruggere la sessione e reindirizzare al login)
        error_log("User session exists for username: " . $_SESSION['username'] . " but user not found in DB.");
    }
}

// Removed all AJAX cart handling logic (GET and POST)
// Removed sync_cart logic
// Removed cart items retrieval for page load

// Fetch services (formerly products)
$servizi = [];
if ($conn) { // Ensure connection is available before querying
    $queryServizi = "SELECT id, nome, categoria, descrizione FROM servizi ORDER BY nome ASC"; // Simplified query for servizi
    $resultServizi = mysqli_query($conn, $queryServizi);
    if ($resultServizi) {
        while ($row = mysqli_fetch_assoc($resultServizi)) {
            $servizi[] = $row;
        }
    }
    mysqli_close($conn); // Close connection after fetching data for the page
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servizi - TorollerCollective</title> <!-- Changed title -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap" rel="stylesheet">
    <?php $basePath = dirname($_SERVER['PHP_SELF']); if ($basePath == '/') $basePath = ''; ?>
    <meta name="base-path" content="<?php echo rtrim(dirname($_SERVER['PHP_SELF']), '/'); ?>">
    <link rel="stylesheet" href="<?php echo $basePath; ?>/style/header.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>/style/shop.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>/style/footer.css">
</head>
<body>
    <?php include 'components/header.php'?>

    <!-- Removed old mobile menu and cart popup HTML -->    <div class="main-content">
        <div class="shop-header">
            <h1 class="shop-title">I Nostri Servizi</h1>
            <p class="shop-subtitle">Esplora la gamma di servizi che offriamo.</p>
        </div>

        <!-- Primo passo: Ricerca -->
        <div class="search-filter-section">
            <form method="GET" action="shop.php" id="searchForm">
                <input type="text" name="search_term" placeholder="Cerca servizio per nome..." value="<?php echo isset($_GET['search_term']) ? $_GET['search_term'] : ''; ?>">
                <select name="category">
                    <option value="">Tutte le categorie</option>
                    <?php 
                    // Fetch distinct categories for filter dropdown
                    $conn_filter = connetti("toroller_semplificato");
                    if ($conn_filter) {
                        $category_query = "SELECT DISTINCT categoria FROM servizi ORDER BY categoria ASC";
                        $category_result = mysqli_query($conn_filter, $category_query);
                        if ($category_result) {
                            while ($cat_row = mysqli_fetch_assoc($category_result)) {
                                $selected = (isset($_GET['category']) && $_GET['category'] == $cat_row['categoria']) ? 'selected' : '';
                                echo '<option value="' . $cat_row['categoria'] . '" ' . $selected . '>' . $cat_row['categoria'] . '</option>';
                            }
                        }
                        mysqli_close($conn_filter);
                    }
                    ?>
                </select>
                <button type="submit">Cerca</button>
            </form>
        </div>

        <div class="products-grid">
            <?php
            // Re-establish connection for filtering/searching if parameters are set
            $conn_display = connetti("toroller_semplificato");
            if ($conn_display) {
                $queryServiziDisplay = "SELECT id, nome, categoria, descrizione FROM servizi";
                $whereClause = "";

                if (!empty($_GET['search_term'])) {
                    $search_term = mysqli_real_escape_string($conn_display, $_GET['search_term']);
                    $whereClause = "WHERE nome LIKE '%$search_term%'";
                }

                if (!empty($_GET['category'])) {
                    $category = mysqli_real_escape_string($conn_display, $_GET['category']);
                    if ($whereClause == "") {
                        $whereClause = "WHERE categoria = '$category'";
                    } else {
                        $whereClause .= " AND categoria = '$category'";
                    }
                }

                $queryServiziDisplay .= " $whereClause ORDER BY nome ASC";

                $resultServiziDisplay = mysqli_query($conn_display, $queryServiziDisplay);

                if ($resultServiziDisplay && mysqli_num_rows($resultServiziDisplay) > 0) {
                    while ($servizio = mysqli_fetch_assoc($resultServiziDisplay)):
                ?>
                        <div class="product-card">
                            <div class="product-info">
                                <h3 class="product-name"><?php echo $servizio['nome']; ?></h3>
                                <p class="product-category">Categoria: <?php echo $servizio['categoria']; ?></p>
                                <a href="servizio_dettaglio.php?id=<?php echo $servizio['id']; ?>" class="view-details-btn">
                                    Vedi Dettagli
                                </a>
                            </div>
                        </div>
                <?php
                    endwhile;
                    mysqli_free_result($resultServiziDisplay);
                } else {
                    echo "<p>Nessun servizio trovato.</p>";
                }
                mysqli_close($conn_display);
            } else {
                echo "<p>Errore di connessione al database.</p>";
            }
            ?>        </div>
    </div>

    <script src="<?php echo $basePath; ?>/components/header.js?v=<?php echo time(); ?>"></script>
    <?php include 'components/footer.php'; ?>
</body>
</html>
