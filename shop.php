<?php
session_start();

// Controllo se l'utente è loggato
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

include("conn.php");

$isLoggedIn = isset($_SESSION['username']);

// Connessione una sola volta
$conn = null;
try {
    $conn = connetti("toroller_semplificato");
    if (!$conn) {
        throw new Exception("Errore di connessione al database");
    }
} catch (Exception $e) {
    error_log("Errore database: " . $e->getMessage());
}

if (isset($_GET['logout'])) {
    session_destroy(); // ✅ unica session_destroy
    header("Location: index.php");
    exit();
}

// Dati utente
$userEmail = '';
$userName = '';
if ($conn && isset($_SESSION['username'])) {
    $current_username = mysqli_real_escape_string($conn, $_SESSION['username']);
    $query = "SELECT username, nome, email FROM utente WHERE username = '$current_username'";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $userEmail = $user['email'];
        $userName = $user['username'];
    } else {
        error_log("User session exists but not found in DB: " . $_SESSION['username']);
    }
}

// Caricamento servizi (lista iniziale)
$servizi = [];
if ($conn) {
    $queryServizi = "SELECT id, nome, categoria, descrizione FROM servizi ORDER BY nome ASC";
    $resultServizi = mysqli_query($conn, $queryServizi);
    if ($resultServizi) {
        while ($row = mysqli_fetch_assoc($resultServizi)) {
            $servizi[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servizi - TorollerCollective</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap" rel="stylesheet">
    <!-- unico modo per far caricare correttament eil front-end (js,css)-->
    <?php $basePath = dirname($_SERVER['PHP_SELF']); if ($basePath == '/') $basePath = ''; ?>
    <meta name="base-path" content="<?php echo rtrim(dirname($_SERVER['PHP_SELF']), '/'); ?>">
    <link rel="stylesheet" href="<?php echo $basePath; ?>/style/header.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>/style/shop.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>/style/footer.css">
</head>
<body>
<?php include 'components/header.php'; ?>

<div class="main-content">
    <div class="shop-header">
        <h1 class="shop-title">I Nostri Servizi</h1>
        <p class="shop-subtitle">Esplora la gamma di servizi che offriamo.</p>
    </div>

    <div class="search-filter-section">
        <form method="GET" action="shop.php" id="searchForm">
            <input type="text" name="search_term" placeholder="Cerca servizio per nome..." value="<?php echo isset($_GET['search_term']) ? $_GET['search_term'] : ''; ?>">
            <select name="category">
                <option value="">Tutte le categorie</option>
                <?php 
                if ($conn) {
                    $category_query = "SELECT DISTINCT categoria FROM servizi ORDER BY categoria ASC";
                    $category_result = mysqli_query($conn, $category_query);
                    if ($category_result) {
                        while ($cat_row = mysqli_fetch_assoc($category_result)) {
                                                                                                    // se 'selected ' l'opzione del filtro selezionato rimane inserito dopo la ricerca
                            $selected = (isset($_GET['category']) && $_GET['category'] == $cat_row['categoria']) ? 'selected' : '';
                            echo '<option value="' . $cat_row['categoria'] . '" ' . $selected . '>' . $cat_row['categoria'] . '</option>';
                        }
                    }
                }
                ?>
            </select>
            <button type="submit">Cerca</button>
        </form>
    </div>


            <!--gestione dei filtri servizi-->
    <div class="products-grid">
        <?php
        if ($conn) {
            $queryServiziDisplay = "SELECT id, nome, categoria, descrizione FROM servizi";
            $whereClause = "";
            // filtro per text by utente
            if (!empty($_GET['search_term'])) {
                $search_term = mysqli_real_escape_string($conn, $_GET['search_term']);
                $whereClause = "WHERE nome LIKE '%$search_term%'";
            }

            // filtro per categoria
            if (!empty($_GET['category'])) {
                $category = mysqli_real_escape_string($conn, $_GET['category']);
                if ($whereClause == "") {
                    $whereClause = "WHERE categoria = '$category'";
                } else {
                    $whereClause .= " AND categoria = '$category'";
                }
            }
            //invio query
            $queryServiziDisplay .= " $whereClause ORDER BY nome ASC";
            $resultServiziDisplay = mysqli_query($conn, $queryServiziDisplay);

            // caricameno dei servizi a front-end
            if ($resultServiziDisplay && mysqli_num_rows($resultServiziDisplay) > 0) {
                while ($servizio = mysqli_fetch_assoc($resultServiziDisplay)) {
                    echo '<div class="product-card">
                            <div class="product-info">
                                <h3 class="product-name">' . $servizio['nome'] . '</h3>
                                <p class="product-category">Categoria: ' . $servizio['categoria'] . '</p>
                                <a href="servizio_dettaglio.php?id=' . $servizio['id'] . '" class="view-details-btn">
                                    Vedi Dettagli
                                </a>
                            </div>
                          </div>';
                }
            } else {
                echo "<p>Nessun servizio trovato.</p>";
            }

            mysqli_close($conn); 
        } else {
            echo "<p>Errore di connessione al database.</p>";
        }
        ?>
    </div>
</div>

<script src="<?php echo $basePath; ?>/components/header.js?v=<?php echo time(); ?>"></script>
<?php include 'components/footer.php'; ?>
</body>
</html>
