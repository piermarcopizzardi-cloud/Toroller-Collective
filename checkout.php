<?php
session_start();
include("conn.php");

// Definisci il percorso base all'inizio dello script
$basePath = dirname($_SERVER['PHP_SELF']);
if ($basePath == '/') $basePath = '';

// Controlla se l'utente è loggato
$isLoggedIn = isset($_SESSION['email']) && isset($_SESSION['password']);

if (!$isLoggedIn) {
    header("Location: login.php");
    exit();
}

// Stabilisci la connessione al database
$conn = connetti('toroller');


// Gestione rimozione dal carrello
if (isset($_POST['remove_from_cart']) && $isLoggedIn) {
    $cartItemId = (int)$_POST['cart_item_id'];
    $email = mysqli_real_escape_string($conn, $_SESSION['email']);
    
    mysqli_query($conn, "DELETE FROM carrello WHERE id = $cartItemId AND email_utente = '$email'");
    
    // Reindirizza per evitare il riinvio del form
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Ottieni il contenuto del carrello
$cartItems = [];
$cartTotal = 0;
if ($conn) {
    $email = mysqli_real_escape_string($conn, $_SESSION['email']);
    // Modified query to include product image
    $cartQuery = "SELECT c.id, c.quantita, p.tipologia as name, p.prezzo as price, p.id as product_id, p.immagine as image 
                 FROM carrello c 
                 JOIN prodotti p ON c.id_prodotto = p.id 
                 WHERE c.email_utente = '$email'";
    $cartResult = mysqli_query($conn, $cartQuery);
    
    if ($cartResult) {
        while ($row = mysqli_fetch_assoc($cartResult)) {
            // Prepend path to image, similar to shop.php
            if (!empty($row['image'])) {
                $row['image_url'] = $basePath . '/assets/products/' . $row['image'];
            } else {
                $row['image_url'] = $basePath . '/assets/product-placeholder.jpg'; // Default placeholder
            }
            $cartItems[] = $row;
            $cartTotal += $row['price'] * $row['quantita'];
        }
    }
}

// Ottieni le informazioni dell'utente se è loggato
$userEmail = '';
if ($isLoggedIn) {
    $email = mysqli_real_escape_string($conn, $_SESSION['email']);
    $query = "SELECT email FROM utente WHERE email = '$email'";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $userEmail = $user['email'];
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - TorollerCollective</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap" rel="stylesheet">
    <meta name="base-path" content="<?php echo $basePath; ?>">
    <link rel="stylesheet" href="<?php echo $basePath; ?>/style/header.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>/style/cart.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>/style/checkout.css">
   
</head>
<body class="checkout-page-identifier">
    <?php include 'components/header.php'; ?>
    
    <main class="checkout-container">
        <div class="checkout-summary">
            <h2 class="section-title">Riepilogo carrello</h2>
            <?php if (empty($cartItems)): ?>
                <p>Il tuo carrello è vuoto.</p>
            <?php else: ?>
                <?php foreach ($cartItems as $item): ?>
                    <div class="cart-item">
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-image-checkout">
                        <div class="item-details">
                            <span class="item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                            <span class="item-quantity">Quantità: <?php echo htmlspecialchars($item['quantita']); ?></span>
                        </div>
                        <div class="item-actions">
                            <span class="item-price">€<?php echo number_format($item['price'] * $item['quantita'], 2); ?></span>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="cart_item_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="remove_from_cart" class="remove-item">&times;</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="cart-total">
                    <span>Totale</span>
                    <span>€<?php echo number_format($cartTotal, 2); ?></span>
                </div>
            <?php endif; ?>
        </div>

        <div class="payment-section">
            <h2 class="section-title">Pagamento</h2>                <div class="payment-options">
                    <div class="payment-option selected">
                        <img src="https://cdn-icons-png.flaticon.com/128/179/179457.png" alt="Credit Card" style="width: 24px; height: 24px;">
                        <div>Carta di credito</div>
                    </div>
                    <div class="payment-option">
                        <img src="https://cdn-icons-png.flaticon.com/128/174/174861.png" alt="PayPal" style="width: 24px; height: 24px;">
                        <div>PayPal</div>
                    </div>
                </div>

            <form class="payment-form">
                <div class="form-group">
                    <label class="form-label">Titolare carta</label>
                    <input type="text" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Numero carta</label>
                    <input type="text" class="form-input" required pattern="[0-9]{16}">
                </div>
                <div class="card-details">
                    <div class="form-group">
                        <label class="form-label">Data di scadenza</label>
                        <input type="month" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">CVV</label>
                        <input type="text" class="form-input" required pattern="[0-9]{3,4}">
                    </div>
                </div>
                <button type="submit" class="submit-button">Completa l'acquisto</button>
            </form>
        </div>
    </main>

    <script src="<?php echo $basePath; ?>/components/header.js"></script>
    <script>
        function selectPaymentMethod(method) {
            const options = document.querySelectorAll('.payment-option');
            options.forEach(option => option.classList.remove('selected'));
            
            if (method === 'card') {
                document.querySelector('.payment-option:first-child').classList.add('selected');
                document.getElementById('payment-form').style.display = 'flex';
            } else {
                document.querySelector('.payment-option:last-child').classList.add('selected');
                document.getElementById('payment-form').style.display = 'none';
                // In una implementazione reale, qui verrebbe attivato Apple Pay
                alert('Apple Pay sarà implementato prossimamente');
            }
        }

        // Per ora preveniamo solo l'invio del form
        document.getElementById('payment-form').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Il sistema di pagamento sarà implementato prossimamente con Stripe');
        });
    </script>
</body>
</html>