<?php
// filepath: c:\xampp\htdocs\Toroller-Collective\components\footer.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Definisci il percorso base
$basePath = dirname($_SERVER['PHP_SELF']);
if ($basePath == '/') $basePath = '';
?>

<footer class="footer">
    <div class="footer-content">
        <div class="footer-section">
            <img src="<?php echo $basePath; ?>/assets/logo1.jpg" alt="TorollerCollective Logo" width="60" height="60" style="object-fit: contain;">
            <h3>TorollerCollective</h3>
            <p>La tua destinazione per il lifestyle streetwear</p>
        </div>
        
        <div class="footer-section">
            <h4>Links Utili</h4>
            <ul>
                <li><a href="<?php echo $basePath; ?>/index.php">Home</a></li>
                <li><a href="<?php echo $basePath; ?>/shop.php">Shop</a></li>
                <?php if (isset($_SESSION['email'])): ?>
                    <li><a href="<?php echo $basePath; ?>/profile.php">Profilo</a></li>
                <?php else: ?>
                    <li><a href="<?php echo $basePath; ?>/login.php">Accedi</a></li>
                    <li><a href="<?php echo $basePath; ?>/registrazione.php">Registrati</a></li>
                <?php endif; ?>
            </ul>
        </div>
        
        <div class="footer-section">
            <h4>Contatti</h4>
            <ul>
                <li>Email: info@torollercollective.it</li>
                <li>Tel: +39 123 456 7890</li>
                <li>Indirizzo: Via Example, 123</li>
            </ul>
        </div>
    </div>
    
    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> TorollerCollective. Tutti i diritti riservati.</p>
    </div>
</footer>
