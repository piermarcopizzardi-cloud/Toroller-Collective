<?php
session_start();

// Controllo se l'utente è loggato
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

include("conn.php");

// Controlla se è stato fornito un ID valido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: shop.php');
    exit();
}

$servizio = null;
$conn = connetti("toroller_semplificato");

if ($conn) {
    $stmt = mysqli_prepare($conn, "SELECT id, nome, categoria, descrizione FROM servizi WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $_GET['id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $servizio = mysqli_fetch_assoc($result);
    } else {
        header('Location: shop.php');
        exit();
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($servizio['nome']); ?> - TorollerCollective</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap" rel="stylesheet">
    <?php $basePath = dirname($_SERVER['PHP_SELF']); if ($basePath == '/') $basePath = ''; ?>
    <link href="<?php echo $basePath; ?>/style/header.css" rel="stylesheet">
    <link href="<?php echo $basePath; ?>/style/shop.css" rel="stylesheet">
</head>
<body>
    <?php include 'components/header.php'; ?>

    <div class="main-content">
        <div class="service-detail-container">
            <a href="shop.php" class="back-button">&larr; Torna ai servizi</a>
            
            <div class="service-detail-card">
                <h1><?php echo htmlspecialchars($servizio['nome']); ?></h1>
                <p class="category">Categoria: <?php echo htmlspecialchars($servizio['categoria']); ?></p>
                <div class="description">
                    <?php echo nl2br(htmlspecialchars($servizio['descrizione'])); ?>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo $basePath; ?>/components/header.js?v=<?php echo time(); ?>"></script>
</body>
</html>
