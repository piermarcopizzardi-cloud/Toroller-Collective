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
    $conn = connetti("toroller_semplificato"); // Corrected DB name
    if (!$conn) {
        throw new Exception("Errore di connessione al database");
    }
} catch (Exception $e) {
    error_log("Errore database: " . $e->getMessage());
}

// Ottieni le informazioni dell'utente se è loggato
$userEmail = '';
$userName = ''; // Changed variable name for clarity, was $userName before
// Removed cart logic

if ($isLoggedIn && $conn) {
    $email_session = mysqli_real_escape_string($conn, $_SESSION['email']);
    // Fetch username based on email from session for display purposes if needed
    $query = "SELECT username, nome FROM utente WHERE email = '$email_session'"; 
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $userEmail = $email_session; // Keep email for session context
        $userName = $user['username']; // Display username
    }
    // Removed cart logic
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
    <title>TorollerCollective - Home</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap" rel="stylesheet">
    <!-- <?php $basePath = dirname($_SERVER['PHP_SELF']); if ($basePath == '/') $basePath = ''; ?> -->
    <!-- <meta name="base-path" content="<?php echo rtrim(dirname($_SERVER['PHP_SELF']), '/'); ?>"> -->
    <link rel="stylesheet" href="<?php echo $basePath; ?>/style/header.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>/style/index.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>/style/footer.css">
</head>
<body>
<?php include 'components/header.php'?>

    <div class="hero-section">
        <h1 class="hero-title">Bike To School</h1>
        <p class="hero-subtitle">Unisciti a noi per condividere la tua passione, connetterti con altri appassionati ed educare alla strada.</p>
        
        <div class="hero-buttons">
            <?php if (!$isLoggedIn): ?>
            <a href="registrazione.php" class="get-started-btn">Unisciti ora</a>
            <a href="login.php" class="login-btn">Accedi</a>
            <?php else: ?>
            <!-- <a href="community.php" class="get-started-btn">Esplora la community</a> Removed community link -->
            <a href="shop.php" class="get-started-btn">Esplora i Servizi</a> <!-- Changed to Servizi -->
            <?php endif; ?>
        </div>
    </div>
    
    <div class="features-section">
        <h2 class="section-title">Cosa offriamo</h2>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <h3 class="feature-title">Servizi Personalizzati</h3> <!-- Updated text -->
                <p class="feature-description">Offriamo servizi pensati per le tue esigenze, dalla manutenzione alla personalizzazione.</p> <!-- Updated text -->
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="feature-title">Consulenza Esperta</h3> <!-- Updated text -->
                <p class="feature-description">Il nostro team di esperti è pronto ad aiutarti a scegliere il meglio per te e la tua bici.</p> <!-- Updated text -->
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11a4 4 0 11-8 0 4 4 0 018 0zm-4-5a.75.75 0 01.75.75V8h1.5a.75.75 0 010 1.5h-1.5v1.25a.75.75 0 01-1.5 0V9.5h-1.5a.75.75 0 010-1.5h1.5V6.75A.75.75 0 0112 6zM3 17.25a.75.75 0 01.75-.75h16.5a.75.75 0 010 1.5H3.75a.75.75 0 01-.75-.75z" />
                    </svg>
                </div>
                <h3 class="feature-title">Qualità Garantita</h3> <!-- Updated text -->
                <p class="feature-description">Utilizziamo solo i migliori materiali e tecniche per garantire la massima qualità dei nostri servizi.</p> <!-- Updated text -->
            </div>
        </div>    </div>
    
    <script src="<?php echo $basePath; ?>/components/header.js?v=<?php echo time(); ?>"></script>
    <?php include 'components/footer.php'; ?>
</body>
</html>
