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
$cartItems = [];
$cartTotal = 0;

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

    // Ottieni il contenuto del carrello per l'utente loggato
    $cartQuery = "SELECT c.id, c.quantita, p.tipologia as name, p.prezzo as price, p.id as product_id 
                 FROM carrello c 
                 JOIN prodotti p ON c.id_prodotto = p.id 
                 WHERE c.email_utente = '$email'";
    $cartResult = mysqli_query($conn, $cartQuery);
    
    if ($cartResult) {
        while ($row = mysqli_fetch_assoc($cartResult)) {
            $cartItems[] = $row;
            $cartTotal += $row['price'] * $row['quantita'];
        }
    }

    // Creazione tabelle del forum se non esistono
    $create_categories = "CREATE TABLE IF NOT EXISTS forum_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $create_topics = "CREATE TABLE IF NOT EXISTS forum_topics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT,
        user_email VARCHAR(255),
        title VARCHAR(255) NOT NULL,
        content TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES forum_categories(id),
        FOREIGN KEY (user_email) REFERENCES utente(email)
    )";
    
    $create_replies = "CREATE TABLE IF NOT EXISTS forum_replies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        topic_id INT,
        user_email VARCHAR(255),
        content TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (topic_id) REFERENCES forum_topics(id),
        FOREIGN KEY (user_email) REFERENCES utente(email)
    )";

    mysqli_query($conn, $create_categories);
    mysqli_query($conn, $create_topics);
    mysqli_query($conn, $create_replies);

    // Inserisci categorie di default se non esistono
    $check_categories = "SELECT COUNT(*) as count FROM forum_categories";
    $result = mysqli_query($conn, $check_categories);
    $count = mysqli_fetch_assoc($result)['count'];

    if ($count == 0) {
        $default_categories = [
            ['name' => 'Generale', 'description' => 'Discussioni generali sulla community'],
            ['name' => 'Eventi', 'description' => 'Discussioni sugli eventi passati e futuri'],
            ['name' => 'Tecnica', 'description' => 'Discussioni tecniche e consigli'],
            ['name' => 'Mercatino', 'description' => 'Compra-vendita tra membri della community']
        ];

        foreach ($default_categories as $category) {
            $name = mysqli_real_escape_string($conn, $category['name']);
            $desc = mysqli_real_escape_string($conn, $category['description']);
            mysqli_query($conn, "INSERT INTO forum_categories (name, description) VALUES ('$name', '$desc')");
        }
    }

    // Gestione delle azioni del forum
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create_topic':
                    $category_id = mysqli_real_escape_string($conn, $_POST['category_id']);
                    $title = mysqli_real_escape_string($conn, $_POST['title']);
                    $content = mysqli_real_escape_string($conn, $_POST['content']);
                    
                    $query = "INSERT INTO forum_topics (category_id, user_email, title, content) 
                             VALUES ('$category_id', '$userEmail', '$title', '$content')";
                    mysqli_query($conn, $query);
                    header("Location: community.php?category=$category_id");
                    exit();
                    break;

                case 'create_reply':
                    $topic_id = mysqli_real_escape_string($conn, $_POST['topic_id']);
                    $content = mysqli_real_escape_string($conn, $_POST['content']);
                    
                    $query = "INSERT INTO forum_replies (topic_id, user_email, content) 
                             VALUES ('$topic_id', '$userEmail', '$content')";
                    mysqli_query($conn, $query);
                    header("Location: community.php?topic=$topic_id");
                    exit();
                    break;
            }
        }
    }

    // Recupera i dati del forum
    $category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;
    $topic_id = isset($_GET['topic']) ? (int)$_GET['topic'] : null;

    // Recupera le categorie
    $categories = [];
    $result = mysqli_query($conn, "SELECT * FROM forum_categories ORDER BY name");
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }

    // Recupera i topic se è selezionata una categoria
    $topics = [];
    if ($category_id) {
        $query = "SELECT t.*, u.nome as author_name, 
                 (SELECT COUNT(*) FROM forum_replies WHERE topic_id = t.id) as reply_count
                 FROM forum_topics t 
                 LEFT JOIN utente u ON t.user_email = u.email
                 WHERE t.category_id = $category_id 
                 ORDER BY t.created_at DESC";
        $result = mysqli_query($conn, $query);
        while ($row = mysqli_fetch_assoc($result)) {
            $topics[] = $row;
        }
    }

    // Recupera un topic specifico e le sue risposte
    $current_topic = null;
    $replies = [];
    if ($topic_id) {
        $query = "SELECT t.*, u.nome as author_name, c.name as category_name 
                 FROM forum_topics t 
                 LEFT JOIN utente u ON t.user_email = u.email
                 LEFT JOIN forum_categories c ON t.category_id = c.id
                 WHERE t.id = $topic_id";
        $result = mysqli_query($conn, $query);
        $current_topic = mysqli_fetch_assoc($result);

        if ($current_topic) {
            $query = "SELECT r.*, u.nome as author_name 
                     FROM forum_replies r 
                     LEFT JOIN utente u ON r.user_email = u.email
                     WHERE r.topic_id = $topic_id 
                     ORDER BY r.created_at";
            $result = mysqli_query($conn, $query);
            while ($row = mysqli_fetch_assoc($result)) {
                $replies[] = $row;
            }
        }
    }

    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community - TorollerCollective</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap" rel="stylesheet">
    <link href="style/community.css" rel="stylesheet">
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
                <a class="nav-link active" href="community.php">Community</a>
                <div class="nav-link-with-icon">
                    <a class="nav-link" href="shop.php">Shop</a>
                    <div class="cart-container">
                        <div class="cart-icon" onclick="toggleCart()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="9" cy="21" r="1"></circle>
                                <circle cx="20" cy="21" r="1"></circle>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                            </svg>
                            <span class="cart-badge"><?php echo array_sum(array_column($cartItems, 'quantita')); ?></span>
                        </div>
                        
                        <!-- Cart Popup -->
                        <div id="cartPopup" class="cart-popup">
                            <div class="cart-popup-header">
                                <h3>Il tuo carrello</h3>
                                <span class="close-cart" onclick="toggleCart()">&times;</span>
                            </div>
                            <div class="cart-items">
                                <?php if (!empty($cartItems)): ?>
                                    <?php foreach ($cartItems as $item): ?>
                                        <div class="cart-item">
                                            <div>
                                                <div class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                                <div class="cart-item-price">€<?php echo number_format($item['price'], 2, ',', '.'); ?> x <?php echo $item['quantita']; ?></div>
                                            </div>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="cart_item_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" name="remove_from_cart" class="remove-item">&times;</button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="empty-cart">Il carrello è vuoto</p>
                                <?php endif; ?>
                            </div>
                            <div class="cart-footer">
                                <div class="cart-total">Totale: €<?php echo number_format($cartTotal, 2, ',', '.'); ?></div>
                                <?php if (!empty($cartItems)): ?>
                                    <a href="checkout.php" class="checkout-btn">Procedi all'acquisto</a>
                                <?php endif; ?>
                            </div>
                        </div>
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

    <div class="mobile-menu">
        <div class="close-menu">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="24" height="24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </div>
        <a class="nav-link" href="index.php">Home</a>
        <a class="nav-link active" href="community.php">Community</a>
        <a class="nav-link" href="shop.php">Shop</a>
        <a class="nav-link" href="eventi.php">Eventi</a>
        
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

    <div class="forum-container">
        <?php if (!$isLoggedIn): ?>
            <div class="login-prompt">
                <p>Per partecipare alle discussioni devi essere registrato</p>
                <a href="login.php" class="get-started-btn">Accedi</a>
                <span style="margin: 0 10px;">o</span>
                <a href="registrazione.php" class="get-started-btn">Registrati</a>
            </div>
        <?php else: ?>
            <?php if ($topic_id && $current_topic): ?>
                <!-- Visualizzazione Topic e Risposte -->
                <div class="breadcrumb">
                    <a href="community.php">Forum</a>
                    <span>/</span>
                    <a href="community.php?category=<?php echo $current_topic['category_id']; ?>"><?php echo htmlspecialchars($current_topic['category_name']); ?></a>
                    <span>/</span>
                    <span><?php echo htmlspecialchars($current_topic['title']); ?></span>
                </div>

                <div class="topic-content">
                    <div class="topic-header">
                        <h1 class="topic-title"><?php echo htmlspecialchars($current_topic['title']); ?></h1>
                        <div class="topic-meta">
                            <span class="topic-author"><?php echo htmlspecialchars($current_topic['author_name']); ?></span>
                            <span class="topic-date"><?php echo date('d/m/Y H:i', strtotime($current_topic['created_at'])); ?></span>
                        </div>
                    </div>
                    <div class="topic-body">
                        <?php echo nl2br(htmlspecialchars($current_topic['content'])); ?>
                    </div>
                </div>

                <div class="reply-list">
                    <?php foreach ($replies as $reply): ?>
                        <div class="reply-item">
                            <div class="reply-header">
                                <span class="reply-author"><?php echo htmlspecialchars($reply['author_name']); ?></span>
                                <span class="reply-date"><?php echo date('d/m/Y H:i', strtotime($reply['created_at'])); ?></span>
                            </div>
                            <div class="reply-content">
                                <?php echo nl2br(htmlspecialchars($reply['content'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <form class="create-reply-form" method="POST">
                    <input type="hidden" name="action" value="create_reply">
                    <input type="hidden" name="topic_id" value="<?php echo $topic_id; ?>">
                    <div class="form-group">
                        <label class="form-label">La tua risposta</label>
                        <textarea class="form-textarea" name="content" required></textarea>
                    </div>
                    <button type="submit" class="form-submit">Rispondi</button>
                </form>

            <?php elseif ($category_id): ?>
                <!-- Lista dei Topic nella Categoria -->
                <div class="breadcrumb">
                    <a href="community.php">Forum</a>
                    <span>/</span>
                    <span><?php echo htmlspecialchars($categories[array_search($category_id, array_column($categories, 'id'))]['name']); ?></span>
                </div>

                <a href="#" class="create-topic-btn" onclick="document.getElementById('create-topic-form').style.display='block'">Crea nuovo topic</a>

                <form id="create-topic-form" class="create-topic-form" method="POST" style="display: none;">
                    <input type="hidden" name="action" value="create_topic">
                    <input type="hidden" name="category_id" value="<?php echo $category_id; ?>">
                    <div class="form-group">
                        <label class="form-label">Titolo</label>
                        <input type="text" class="form-input" name="title" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contenuto</label>
                        <textarea class="form-textarea" name="content" required></textarea>
                    </div>
                    <button type="submit" class="form-submit">Pubblica Topic</button>
                </form>

                <div class="topic-list">
                    <?php foreach ($topics as $topic): ?>
                        <a href="community.php?topic=<?php echo $topic['id']; ?>" class="topic-item">
                            <div class="topic-info">
                                <div class="topic-main">
                                    <h2 class="topic-title"><?php echo htmlspecialchars($topic['title']); ?></h2>
                                    <div class="topic-meta">
                                        <span class="topic-author"><?php echo htmlspecialchars($topic['author_name']); ?></span>
                                        <span><?php echo date('d/m/Y H:i', strtotime($topic['created_at'])); ?></span>
                                    </div>
                                </div>
                                <div class="topic-stats">
                                    <?php echo $topic['reply_count']; ?> risposte
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>
                <!-- Lista delle Categorie -->
                <div class="forum-header">
                    <h1 class="forum-title">Forum della Community</h1>
                    <p class="forum-description">Partecipa alle discussioni, condividi le tue esperienze e connettiti con altri membri della community.</p>
                </div>

                <div class="forum-categories">
                    <?php foreach ($categories as $category): ?>
                        <a href="community.php?category=<?php echo $category['id']; ?>" class="category-card">
                            <h2 class="category-title"><?php echo htmlspecialchars($category['name']); ?></h2>
                            <p class="category-description"><?php echo htmlspecialchars($category['description']); ?></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hamburger = document.querySelector('.hamburger-menu');
            const closeMenu = document.querySelector('.close-menu');
            const mobileMenu = document.querySelector('.mobile-menu');
            const mobileLinks = document.querySelectorAll('.mobile-menu .nav-link, .mobile-menu .auth-buttons a');

            function toggleMenu() {
                mobileMenu.classList.toggle('active');
                document.body.style.overflow = mobileMenu.classList.contains('active') ? 'hidden' : '';
            }

            hamburger.addEventListener('click', toggleMenu);
            closeMenu.addEventListener('click', toggleMenu);

            // Close menu when clicking on links
            mobileLinks.forEach(link => {
                link.addEventListener('click', toggleMenu);
            });
        });

        function toggleCart() {
            const cartPopup = document.getElementById('cartPopup');
            cartPopup.style.display = cartPopup.style.display === 'block' ? 'none' : 'block';
        }

        // Chiudi il popup del carrello quando si clicca fuori
        document.addEventListener('click', function(event) {
            const cartPopup = document.getElementById('cartPopup');
            const cartIcon = document.querySelector('.cart-icon');
            
            if (!cartPopup.contains(event.target) && !cartIcon.contains(event.target)) {
                cartPopup.style.display = 'none';
            }
        });

        // Previeni la chiusura quando si clicca dentro il carrello
        document.getElementById('cartPopup').addEventListener('click', function(event) {
            event.stopPropagation();
        });
    </script>
</body>
</html>
