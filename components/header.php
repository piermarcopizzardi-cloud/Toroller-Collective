<?php
session_start();

// Controlla se l'utente è loggato
$isLoggedIn = isset($_SESSION['email']) && isset($_SESSION['password']);

// Definisci il percorso base
$basePath = dirname($_SERVER['PHP_SELF']);
if ($basePath == '/') $basePath = '';

// Ottieni le informazioni dell'utente se è loggato
$userEmail = '';
if ($isLoggedIn && isset($_SESSION['email'])) {
    include_once(__DIR__ . "/../conn.php");
    $conn = connetti('toroller');
    
    if ($conn) {
        $email = mysqli_real_escape_string($conn, $_SESSION['email']);
        $query = "SELECT email, amministratore FROM utente WHERE email = '$email'";
        $result = mysqli_query($conn, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            $userEmail = $user['email'];
            $isAdmin = $user['amministratore'] == 1;
        }
    }
}

// Get cart items count if logged in
$cartCount = 0;
if ($isLoggedIn && isset($_SESSION['email']) && $conn) {
    $email = mysqli_real_escape_string($conn, $_SESSION['email']);
    $cartQuery = "SELECT SUM(quantita) as total FROM carrello WHERE email_utente = '$email'";
    $cartResult = mysqli_query($conn, $cartQuery);
    if ($cartResult) {
        $row = mysqli_fetch_assoc($cartResult);
        $cartCount = $row['total'] ?: 0;
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
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'community.php' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>/community.php">Community</a>
            <div class="nav-link-with-icon">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'shop.php' || basename($_SERVER['PHP_SELF']) === 'checkout.php' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>/shop.php">Shop</a>
                <div class="cart-container">
                    <div class="cart-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                        <span class="cart-badge"><?php echo $cartCount; ?></span>
                    </div>
                </div>
            </div>
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'eventi.php' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>/eventi.php">Eventi</a>
        </div>
        
        <?php if ($isLoggedIn): ?>
            <div class="user-menu">
                <?php if (isset($isAdmin) && $isAdmin): ?>
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
    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>/index.php">Home</a>
    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'community.php' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>/community.php">Community</a>
    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'shop.php' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>/shop.php">Shop</a>
    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'eventi.php' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>/eventi.php">Eventi</a>
    
    <div class="auth-buttons">
        <?php if ($isLoggedIn): ?>
        <div class="user-menu">
            <?php if (isset($isAdmin) && $isAdmin): ?>
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
