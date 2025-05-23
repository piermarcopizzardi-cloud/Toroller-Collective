<?php
session_start();

// Controlla se l'utente è loggato
$isLoggedIn = isset($_SESSION['email']) && isset($_SESSION['password']);

// Definisci il percorso base
$basePath = dirname($_SERVER['PHP_SELF']);
if ($basePath == '/') $basePath = '';

// Ottieni le informazioni dell'utente se è loggato
$userEmail = '';
$isAdmin = false; // Inizializza isAdmin
if ($isLoggedIn && isset($_SESSION['email'])) {
    include_once(__DIR__ . "/../conn.php");
    $conn = connetti('toroller_semplificato'); // Corrected database name
    
    if ($conn) {
        $email = mysqli_real_escape_string($conn, $_SESSION['email']);
        $query = "SELECT email, amministratore FROM utente WHERE email = '$email'";
        $result = mysqli_query($conn, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            $userEmail = $user['email'];
            $isAdmin = $user['amministratore'] == 1;
        }
        // Non chiudere la connessione qui se serve ancora
    }
}
?>

<div class="header">
    <div class="logo-container">
        <img src="<?php echo $basePath; ?>/assets/logo1.jpg" alt="TorollerCollective Logo" width="80" height="80" style="object-fit: contain;">
        <div class="logo-text">TorollerCollective</div>
    </div>
    
    <div class="nav-menu">
        <div class="nav-links">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>/index.php">Home</a>
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'shop.php' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>/shop.php">Shop</a>
        </div>
        
        <?php if ($isLoggedIn): ?>
            <div class="user-menu">
                <?php if ($isAdmin): // Usa la variabile $isAdmin correttamente inizializzata ?>
                    <a href="<?php echo $basePath; ?>/admin.php" class="user-email"><?php echo htmlspecialchars($userEmail); ?></a>
                <?php else: ?>
                    <a href="<?php echo $basePath; ?>/utente_cambio_pws.php" class="user-email"><?php echo htmlspecialchars($userEmail); ?></a>
                <?php endif; ?>
                <a href="<?php echo $basePath; ?>/?logout=1" class="logout-btn">Logout</a>
            </div>
        <?php else: ?>
            <div class="auth-buttons">
                <a href="<?php echo $basePath; ?>/login.php" class="login-btn">Login</a>
                <a href="<?php echo $basePath; ?>/registrazione.php" class="get-started-btn">Get started</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Hamburger Menu -->
    <div class="hamburger-menu">
        <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </div>

    <!-- Mobile Menu (nascosto di default) -->
    <div class="mobile-menu">
        <div class="close-menu">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </div>
        <div class="mobile-nav-links">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>/index.php">Home</a>
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'shop.php' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>/shop.php">Shop</a>
        </div>
        <div class="mobile-auth-buttons">
            <?php if ($isLoggedIn): ?>
                <div class="user-menu-mobile">
                    <?php if ($isAdmin): ?>
                        <a href="<?php echo $basePath; ?>/admin.php" class="user-email"><?php echo htmlspecialchars($userEmail); ?></a>
                    <?php else: ?>
                        <a href="<?php echo $basePath; ?>/utente_cambio_pws.php" class="user-email"><?php echo htmlspecialchars($userEmail); ?></a>
                    <?php endif; ?>
                    <a href="<?php echo $basePath; ?>/?logout=1" class="logout-btn">Logout</a>
                </div>
            <?php else: ?>
                <a href="<?php echo $basePath; ?>/login.php" class="login-btn">Login</a>
                <a href="<?php echo $basePath; ?>/registrazione.php" class="get-started-btn">Get started</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="<?php echo $basePath; ?>/components/header.js?v=<?php echo time(); ?>"></script>
