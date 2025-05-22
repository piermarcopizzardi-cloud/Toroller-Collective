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

// Verifica che sia stato fornito un ID evento valido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: eventi.php");
    exit();
}

$eventId = intval($_GET['id']);

$conn = null;
try {
    $conn = connetti("toroller");
    if (!$conn) {
        throw new Exception("Errore di connessione al database");
    }
} catch (Exception $e) {
    error_log("Errore database: " . $e->getMessage());
    header("Location: eventi.php?error=db");
    exit();
}

// Ottieni le informazioni dell'utente se è loggato
$userEmail = '';
$userName = '';
$cartItems = [];
$cartTotal = 0;

if ($isLoggedIn && $conn) {
    $email = mysqli_real_escape_string($conn, $_SESSION['email']);
    $query = "SELECT nome, email FROM utente WHERE email = '$email'";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $userEmail = $user['email'];
        $userName = $user['nome'];
    }
}
$selectedEvent = null;
if ($conn) {
    $query = "SELECT * FROM eventi WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $eventId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($event = mysqli_fetch_assoc($result)) {
        $selectedEvent = [
            'id' => $event['id'],
            'title' => $event['titolo'],
            'date' => $event['data'],
            'location' => $event['luogo'],
            'description' => $event['descrizione'],
            'image' => 'assets/community-image.jpg',
            'time' => '19:00 - 22:00',
            'participants' => 100,
            'organizer' => 'TorollerCollective',
            'registration_deadline' => date('Y-m-d', strtotime($event['data'] . ' -5 days')),
            'instagram_links' => [
                ['title' => 'Seguici su Instagram', 'url' => 'https://www.instagram.com/torollercollective']
            ]
        ];
    }
    
    // Recupera gli eventi correlati
    $relatedEvents = [];
    $query = "SELECT * FROM eventi WHERE id != ? ORDER BY data ASC LIMIT 2";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $eventId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($event = mysqli_fetch_assoc($result)) {
        $relatedEvents[] = [
            'id' => $event['id'],
            'title' => $event['titolo'],
            'date' => $event['data'],
            'location' => $event['luogo'],
            'description' => $event['descrizione'],
            'image' => 'assets/community-image.jpg'
        ];
    }
    mysqli_close($conn);
} else {
    header("Location: eventi.php?error=not_found");
    exit();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($selectedEvent['title']); ?> - TorollerCollective</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap" rel="stylesheet">
    <?php $basePath = dirname($_SERVER['PHP_SELF']); if ($basePath == '/') $basePath = ''; ?>
    <meta name="base-path" content="<?php echo rtrim(dirname($_SERVER['PHP_SELF']), '/'); ?>">
    <link href="<?php echo $basePath; ?>/style/evento-dettaglio.css" rel="stylesheet">
    <link href="<?php echo $basePath; ?>/style/header.css" rel="stylesheet">
    <link href="<?php echo $basePath; ?>/style/cart.css" rel="stylesheet">
    <link href="<?php echo $basePath; ?>/style/eventi.css" rel="stylesheet">
</head>
<body>
    <?php include 'components/header.php'?>

    <div class="mobile-menu">
        <div class="close-menu">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="24" height="24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </div>
        <a class="nav-link" href="index.php">Home</a>
        <a class="nav-link" href="community.php">Community</a>
        <a class="nav-link" href="shop.php">Shop</a>
        <a class="nav-link active" href="eventi.php">Eventi</a>
        
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

    <div class="event-detail-container">
        <div class="event-header">
            <a href="eventi.php" class="back-button">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5M12 19l-7-7 7-7"></path>
                </svg>
                Torna agli eventi
            </a>
            <h1 class="event-title"><?php echo htmlspecialchars($selectedEvent['title']); ?></h1>
        </div>

        <div class="event-hero">
            <img src="<?php echo htmlspecialchars($selectedEvent['image']); ?>" alt="<?php echo htmlspecialchars($selectedEvent['title']); ?>" class="event-hero-image">
        </div>

        <div class="event-content">
            <div class="event-main">
                <div class="event-info-card">
                    <div class="event-info-item">
                        <div class="info-label">Data</div>
                        <div class="info-value"><?php echo date('d/m/Y', strtotime($selectedEvent['date'])); ?></div>
                    </div>
                    <div class="event-info-item">
                        <div class="info-label">Orario</div>
                        <div class="info-value"><?php echo htmlspecialchars($selectedEvent['time']); ?></div>
                    </div>
                    <div class="event-info-item">
                        <div class="info-label">Luogo</div>
                        <div class="info-value"><?php echo htmlspecialchars($selectedEvent['location']); ?></div>
                    </div>
                    <div class="event-info-item">
                        <div class="info-label">Partecipanti</div>
                        <div class="info-value"><?php echo htmlspecialchars($selectedEvent['participants']); ?></div>
                    </div>
                    <div class="event-info-item">
                        <div class="info-label">Organizzatore</div>
                        <div class="info-value"><?php echo htmlspecialchars($selectedEvent['organizer']); ?></div>
                    </div>
                    <div class="event-info-item">
                        <div class="info-label">Scadenza iscrizione</div>
                        <div class="info-value"><?php echo date('d/m/Y', strtotime($selectedEvent['registration_deadline'])); ?></div>
                    </div>
                </div>

                <div class="event-description">
                    <h2>Descrizione dell'evento</h2>
                    <p><?php echo nl2br(htmlspecialchars($selectedEvent['full_description'])); ?></p>
                </div>

                <div class="event-registration">
                    <h2>Iscrizione all'evento</h2>
                    <form class="registration-form">
                        <div class="form-group">
                            <label for="name">Nome e Cognome</label>
                            <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($userName); ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($userEmail); ?>">
                        </div>
                        <div class="form-group">
                            <label for="participants">Numero di partecipanti</label>
                            <select id="participants" name="participants">
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="notes">Note aggiuntive</label>
                            <textarea id="notes" name="notes" rows="4"></textarea>
                        </div>
                        <button type="submit" class="registration-button">Conferma iscrizione</button>
                    </form>
                </div>
            </div>

            <div class="event-sidebar">
                <div class="instagram-content">
                    <h2>Contenuti Instagram</h2>
                    <div class="instagram-links">
                        <?php if (isset($selectedEvent['instagram_links']) && !empty($selectedEvent['instagram_links'])): ?>
                            <?php foreach ($selectedEvent['instagram_links'] as $link): ?>
                                <a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank" class="instagram-link">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                                        <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                                        <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                                    </svg>
                                    <?php echo htmlspecialchars($link['title']); ?>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>Nessun contenuto Instagram disponibile per questo evento.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="related-events">
                    <h2>Eventi correlati</h2>
                    <div class="related-events-list">
                        <?php 
                        $relatedEvents = array_filter($events, function($event) use ($selectedEvent) {
                            return $event['id'] != $selectedEvent['id'];
                        });
                        
                        foreach (array_slice($relatedEvents, 0, 2) as $event): 
                        ?>
                            <div class="related-event-card">
                                <img src="<?php echo htmlspecialchars($event['image']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>" class="related-event-image">
                                <div class="related-event-content">
                                    <div class="related-event-date"><?php echo date('d/m/Y', strtotime($event['date'])); ?></div>
                                    <h3 class="related-event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                                    <a href="evento-dettaglio.php?id=<?php echo $event['id']; ?>" class="view-event-btn">Visualizza</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo $basePath; ?>/components/header.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Menu mobile
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

            // Form submission con feedback all'utente
            const registrationForm = document.querySelector('.registration-form');
            if (registrationForm) {
                registrationForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const submitButton = this.querySelector('.registration-button');
                    const originalText = submitButton.textContent;
                    
                    // Cambia il testo del pulsante per dare feedback immediato
                    submitButton.textContent = 'Registrazione in corso...';
                    submitButton.disabled = true;
                    
                    // Simula una richiesta al server
                    setTimeout(function() {
                        // Crea un elemento per il messaggio di successo
                        const successMessage = document.createElement('div');
                        successMessage.className = 'success-message';
                        successMessage.innerHTML = `
                            <div class="success-icon">✓</div>
                            <div class="success-text">
                                <h3>Iscrizione completata!</h3>
                                <p>La tua partecipazione all'evento è stata registrata con successo.</p>
                            </div>
                        `;
                        
                        // Sostituisci il form con il messaggio di successo
                        registrationForm.style.opacity = 0;
                        setTimeout(() => {
                            registrationForm.parentNode.replaceChild(successMessage, registrationForm);
                            successMessage.style.opacity = 0;
                            setTimeout(() => {
                                successMessage.style.opacity = 1;
                            }, 50);
                        }, 300);
                    }, 1500);
                });
            }
            
            // Apre le immagini in modalità lightbox quando cliccate
            const eventHeroImage = document.querySelector('.event-hero-image');
            if (eventHeroImage) {
                eventHeroImage.addEventListener('click', function() {
                    const lightbox = document.createElement('div');
                    lightbox.className = 'lightbox';
                    lightbox.innerHTML = `
                        <div class="lightbox-content">
                            <img src="${this.src}" alt="${this.alt}">
                            <span class="close-lightbox">&times;</span>
                        </div>
                    `;
                    document.body.appendChild(lightbox);
                    
                    // Aggiungi stile lightbox dinamicamente
                    const style = document.createElement('style');
                    style.textContent = `
                        .lightbox {
                            position: fixed;
                            top: 0;
                            left: 0;
                            width: 100%;
                            height: 100%;
                            background-color: rgba(0, 0, 0, 0.9);
                            display: flex;
                            justify-content: center;
                            align-items: center;
                            z-index: 9999;
                            opacity: 0;
                            transition: opacity 0.3s;
                        }
                        .lightbox.active {
                            opacity: 1;
                        }
                        .lightbox-content {
                            position: relative;
                            max-width: 90%;
                            max-height: 90%;
                        }
                        .lightbox img {
                            max-width: 100%;
                            max-height: 90vh;
                            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
                        }
                        .close-lightbox {
                            position: absolute;
                            top: -40px;
                            right: 0;
                            color: white;
                            font-size: 30px;
                            cursor: pointer;
                        }
                        .success-message {
                            background-color: #f2fff2;
                            border-left: 5px solid #00c853;
                            padding: 20px;
                            border-radius: 8px;
                            display: flex;
                            align-items: center;
                            gap: 20px;
                            opacity: 0;
                            transition: opacity 0.5s;
                        }
                        .success-icon {
                            background-color: #00c853;
                            color: white;
                            width: 50px;
                            height: 50px;
                            border-radius: 50%;
                            display: flex;
                            justify-content: center;
                            align-items: center;
                            font-size: 28px;
                        }
                        .success-text h3 {
                            margin: 0 0 5px 0;
                            color: #00c853;
                        }
                        .success-text p {
                            margin: 0;
                            color: #444;
                        }
                    `;
                    document.head.appendChild(style);
                    
                    // Attiva il lightbox dopo un breve ritardo per l'animazione
                    setTimeout(() => {
                        lightbox.classList.add('active');
                    }, 10);
                    
                    // Chiudi lightbox con il pulsante di chiusura
                    const closeLightbox = lightbox.querySelector('.close-lightbox');
                    closeLightbox.addEventListener('click', function() {
                        lightbox.classList.remove('active');
                        setTimeout(() => {
                            document.body.removeChild(lightbox);
                        }, 300);
                    });
                    
                    // Chiudi lightbox cliccando sullo sfondo
                    lightbox.addEventListener('click', function(e) {
                        if (e.target === lightbox) {
                            lightbox.classList.remove('active');
                            setTimeout(() => {
                                document.body.removeChild(lightbox);
                            }, 300);
                        }
                    });
                });
            }
            
            // Anima i link Instagram al passaggio del mouse
            const instagramLinks = document.querySelectorAll('.instagram-link');
            instagramLinks.forEach(link => {
                link.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 5px 15px rgba(0, 0, 0, 0.1)';
                });
                link.addEventListener('mouseleave', function() {
                    this.style.transform = '';
                    this.style.boxShadow = '';
                });
            });
        });
    </script>
</body>
</html>
