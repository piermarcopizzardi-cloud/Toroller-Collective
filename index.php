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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }
        
        body {
            width: 100%;
            min-height: 100vh;
            background-color: #ffffff;
        }
        
        .header {
            width: 100%;
            height: 118px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-left: 110px;
            padding-right: 110px;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo-text {
            color: #04CD00;
            font-size: 30px;
            font-weight: 800;
            font-family: 'Inter', sans-serif;
        }
        
        .nav-menu {
            display: flex;
            align-items: center;
            gap: 24px;
        }
        
        .nav-links {
            display: flex;
            align-items: center;
            gap: 33px;
        }
        
        .nav-link {
            color: #BDD3C6;
            font-size: 18px;
            cursor: pointer;
        }
        
        .nav-link.active {
            color: #04CD00;
            font-weight: 600;
        }
        
        .nav-link-with-icon {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .auth-buttons {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .login-btn {
            color: #BDD3C6;
            font-size: 16px;
            padding: 18px 24px;
            border: 1px solid #7FE47E;
            border-radius: 30px;
            text-decoration: none;
        }
        
        .get-started-btn {
            color: #ffffff;
            font-size: 16px;
            font-weight: 700;
            padding: 18px 24px;
            background-color: #04CD00;
            border-radius: 30px;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 8px 16px;
            border: 1px solid #7FE47E;
            border-radius: 30px;
        }
        
        .user-email {
            color: #04CD00;
            font-size: 16px;
            font-weight: 600;
        }
        
        .logout-btn {
            color: #BDD3C6;
            text-decoration: none;
            font-size: 14px;
        }
        
        .logout-btn:hover {
            color: #04CD00;
        }
        
        .hamburger-menu {
            display: none;
        }
        
        .hero-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 100px 110px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero-title {
            color: #04CD00;
            font-size: 64px;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 24px;
            max-width: 800px;
        }
        
        .hero-subtitle {
            color: #333;
            font-size: 24px;
            line-height: 1.5;
            margin-bottom: 40px;
            max-width: 700px;
        }
        
        .hero-buttons {
            display: flex;
            gap: 16px;
            margin-bottom: 60px;
        }
        
        .hero-image {
            width: 100%;
            max-width: 1000px;
            height: auto;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .features-section {
            padding: 80px 110px;
            background-color: #F9FAFB;
        }
        
        .section-title {
            color: #04CD00;
            font-size: 40px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 60px;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }
        
        .feature-card {
            background-color: #ffffff;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .feature-icon {
            width: 60px;
            height: 60px;
            background-color: #E6F7E6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .feature-icon svg {
            width: 30px;
            height: 30px;
            color: #04CD00;
        }
        
        .feature-title {
            color: #333;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 12px;
        }
        
        .feature-description {
            color: #6B7280;
            font-size: 16px;
            line-height: 1.5;
        }
        
        .community-section {
            padding: 80px 110px;
            display: flex;
            align-items: center;
            gap: 60px;
        }
        
        .community-image {
            flex: 1;
            max-width: 500px;
            border-radius: 20px;
            overflow: hidden;
        }
        
        .community-image img {
            width: 100%;
            height: auto;
            display: block;
        }
        
        .community-content {
            flex: 1;
        }
        
        .community-title {
            color: #04CD00;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 24px;
        }
        
        .community-description {
            color: #333;
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .community-features {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-bottom: 30px;
        }
        
        .community-feature {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        
        .community-feature-text {
            color: #333;
            font-size: 18px;
        }
        
        .cta-section {
            padding: 80px 110px;
            background-color: #04CD00;
            text-align: center;
        }
        
        .cta-title {
            color: #ffffff;
            font-size: 40px;
            font-weight: 700;
            margin-bottom: 24px;
        }
        
        .cta-description {
            color: #ffffff;
            font-size: 20px;
            line-height: 1.5;
            margin-bottom: 40px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-button {
            display: inline-block;
            background-color: #ffffff;
            color: #04CD00;
            font-size: 18px;
            font-weight: 700;
            padding: 18px 36px;
            border-radius: 30px;
            text-decoration: none;
        }
        
        .footer {
            padding: 60px 110px 30px;
            background-color: #1F2937;
            color: #ffffff;
        }
        
        .footer-content {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }
        
        .footer-logo {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .footer-logo-text {
            color: #04CD00;
            font-size: 24px;
            font-weight: 800;
        }
        
        .footer-description {
            color: #D1D5DB;
            max-width: 300px;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .footer-links {
            display: flex;
            gap: 80px;
        }
        
        .footer-column {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .footer-column-title {
            color: #ffffff;
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .footer-link {
            color: #D1D5DB;
            font-size: 14px;
            text-decoration: none;
        }
        
        .footer-link:hover {
            color: #04CD00;
        }
        
        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 30px;
            border-top: 1px solid #374151;
        }
        
        .footer-copyright {
            color: #D1D5DB;
            font-size: 14px;
        }
        
        .footer-social {
            display: flex;
            gap: 16px;
        }
        
        .footer-social-icon {
            width: 36px;
            height: 36px;
            background-color: #374151;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .footer-social-icon svg {
            width: 20px;
            height: 20px;
            color: #ffffff;
        }
        
        @media (max-width: 991px) {
            .header {
                padding-left: 40px;
                padding-right: 40px;
            }
            
            .hero-section,
            .features-section,
            .community-section,
            .cta-section,
            .footer {
                padding-left: 40px;
                padding-right: 40px;
            }
            
            .hero-title {
                font-size: 48px;
            }
            
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .community-section {
                flex-direction: column;
            }
            
            .community-image {
                max-width: 100%;
            }
            
            .footer-content {
                flex-direction: column;
                gap: 40px;
            }
            
            .footer-links {
                flex-wrap: wrap;
                gap: 40px;
            }
        }
        
        @media (max-width: 640px) {
            .header {
                padding-left: 20px;
                padding-right: 20px;
            }
            
            .nav-menu {
                display: none;
            }
            
            .hamburger-menu {
                display: block;
                color: #04CD00;
            }
            
            .hero-section,
            .features-section,
            .community-section,
            .cta-section,
            .footer {
                padding-left: 20px;
                padding-right: 20px;
            }
            
            .hero-title {
                font-size: 36px;
            }
            
            .hero-subtitle {
                font-size: 18px;
            }
            
            .hero-buttons {
                flex-direction: column;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .section-title {
                font-size: 32px;
            }
            
            .footer-bottom {
                flex-direction: column;
                gap: 20px;
            }
        }
    </style>
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
                <a class="nav-link" href="community.php">Community</a>
                <div class="nav-link-with-icon">
                    <a class="nav-link" href="shop.php">Shop</a>
                    <div>
                        <svg width="12" height="12" viewBox="0 0 66 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <text fill="#BDD3C6" xml:space="preserve" style="white-space: pre" font-family="DM Sans" font-size="18" letter-spacing="0px"><tspan x="0.475952" y="15.2126">Shop</tspan></text>
                            <path d="M53.3334 6.15796L59.1667 11.9913L65 6.15796" stroke="#211F54" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </div>
                </div>
                <a class="nav-link" href="eventi.php">Eventi</a>
            </div>
            
            <div class="auth-buttons">
                <?php if ($isLoggedIn): ?>
                <div class="user-menu">
                    <span class="user-email"><?php echo htmlspecialchars($userEmail); ?></span>
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
    
    <div class="hero-section">
        <h1 class="hero-title">Bike To School</h1>
        <p class="hero-subtitle">Unisciti a noi per condividere la tua passione, connetterti con altri appassionati ed educare alla strada.</p>
        
        <div class="hero-buttons">
            <?php if (!$isLoggedIn): ?>
            <a href="registrazione.php" class="get-started-btn">Unisciti ora</a>
            <a href="login.php" class="login-btn">Accedi</a>
            <?php else: ?>
            <a href="#community" class="get-started-btn">Esplora la community</a>
            <?php endif; ?>
        </div>
        
        <img src="assets/hero-image.jpg" alt="TorollerCollective Community" class="hero-image">
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
                <h3 class="feature-title">Community Attiva</h3>
                <p class="feature-description">Connettiti con altri appassionati, condividi esperienze e partecipa a discussioni stimolanti.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="feature-title">Eventi Esclusivi</h3>
                <p class="feature-description">Partecipa a eventi esclusivi, workshop e incontri organizzati per la nostra community.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11a4 4 0 11-8 0 4 4 0 018 0zm-4-5a.75.75 0 01.75.75V8h1.5a.75.75 0 010 1.5h-1.5v1.25a.75.75 0 01-1.5 0V9.5h-1.5a.75.75 0 010-1.5h1.5V6.75A.75.75 0 0112 6zM3 17.25a.75.75 0 01.75-.75h16.5a.75.75 0 010 1.5H3.75a.75.75 0 01-.75-.75z" />
                    </svg>
                </div>
                <h3 class="feature-title">Prodotti Esclusivi</h3>
                <p class="feature-description">Accedi al nostro shop con prodotti esclusivi selezionati per la nostra community.</p>
            </div>
        </div>
    </div>
    
    <div id="community" class="community-section">
        <div class="community-image">
            <img src="assets/community-image.jpg" alt="TorollerCollective Community">
        </div>
        
        <div class="community-content">
            <h2 class="community-title">Una community in crescita</h2>
            <p class="community-description">TorollerCollective è una community di appassionati che condividono interessi, esperienze e conoscenze. Siamo un gruppo in continua crescita, unito dalla passione e dal desiderio di creare connessioni significative.</p>
            
            <div class="community-features">
                <div class="community-feature">
                    <div>
                        <svg width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g clip-path="url(#clip0_16_1289)">
                                <path d="M13 26C20.1799 26 26 20.1799 26 13C26 5.8201 20.1799 0 13 0C5.8201 0 0 5.8201 0 13C0 20.1799 5.8201 26 13 26Z" fill="#04CD00"></path>
                                <path d="M7.11682 13.8405L10.4786 17.2023L18.8832 8.79773" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            </g>
                            <defs>
                                <clipPath id="clip0_16_1289">
                                    <rect width="26" height="26" fill="white"></rect>
                                </clipPath>
                            </defs>
                        </svg>
                    </div>
                    <div class="community-feature-text">Oltre 5.000 membri attivi</div>
                </div>
                
                <div class="community-feature">
                    <div>
                        <svg width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g clip-path="url(#clip0_16_1296)">
                                <path d="M13 26C20.1799 26 26 20.1799 26 13C26 5.8201 20.1799 0 13 0C5.8201 0 0 5.8201 0 13C0 20.1799 5.8201 26 13 26Z" fill="#04CD00"></path>
                                <path d="M7.11682 13.8405L10.4786 17.2023L18.8832 8.79773" fill="#04CD00"></path>
                                <path d="M7.11682 13.8405L10.4786 17.2023L18.8832 8.79773" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            </g>
                            <defs>
                                <clipPath id="clip0_16_1296">
                                    <rect width="26" height="26" fill="white"></rect>
                                </clipPath>
                            </defs>
                        </svg>
                    </div>
                    <div class="community-feature-text">Eventi mensili in tutta Italia</div>
                </div>
                
                <div class="community-feature">
                    <div>
                        <svg width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g clip-path="url(#clip0_16_1296)">
                                <path d="M13 26C20.1799 26 26 20.1799 26 13C26 5.8201 20.1799 0 13 0C5.8201 0 0 5.8201 0 13C0 20.1799 5.8201 26 13 26Z" fill="#04CD00"></path>
                                <path d="M7.11682 13.8405L10.4786 17.2023L18.8832 8.79773" fill="#04CD00"></path>
                                <path d="M7.11682 13.8405L10.4786 17.2023L18.8832 8.79773" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            </g>
                            <defs>
                                <clipPath id="clip0_16_1296">
                                    <rect width="26" height="26" fill="white"></rect>
                                </clipPath>
                            </defs>
                        </svg>
                    </div>
                    <div class="community-feature-text">Collaborazioni con brand e artisti</div>
                </div>
            </div>
            
            <?php if (!$isLoggedIn): ?>
            <a href="registrazione.php" class="get-started-btn">Unisciti a noi</a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="cta-section">
        <h2 class="cta-title">Pronto a far parte della nostra community?</h2>
        <p class="cta-description">Unisciti a TorollerCollective oggi stesso e scopri un mondo di opportunità, connessioni e esperienze condivise.</p>
        
        <?php if (!$isLoggedIn): ?>
        <a href="registrazione.php" class="cta-button">Inizia ora</a>
        <?php else: ?>
        <a href="/eventi.php" class="cta-button">Esplora gli eventi</a>
        <?php endif; ?>
    </div>
    
    <div class="footer">
        <div class="footer-content">
            <div class="footer-logo">
                <div class="footer-logo-text">TorollerCollective</div>
                <p class="footer-description">Una community di appassionati uniti dalla passione e dal desiderio di creare connessioni significative.</p>
            </div>
            
            <div class="footer-links">
                <div class="footer-column">
                    <div class="footer-column-title">Navigazione</div>
                    <a href="index.php" class="footer-link">Home</a>
                    <a href="community.php" class="footer-link">Community</a>
                    <a href="shop.php" class="footer-link">Shop</a>
                    <a href="/eventi.php" class="footer-link">Eventi</a>
                </div>
                
                <div class="footer-column">
                    <div class="footer-column-title">Account</div>
                    <?php if (!$isLoggedIn): ?>
                    <a href="login.php" class="footer-link">Accedi</a>
                    <a href="registrazione.php" class="footer-link">Registrati</a>
                    <?php else: ?>
                    <a href="#" class="footer-link">Il mio profilo</a>
                    <a href="index.php?logout=1" class="footer-link">Logout</a>
                    <?php endif; ?>
                    <a href="#" class="footer-link">Assistenza</a>
                </div>
                
                <div class="footer-column">
                    <div class="footer-column-title">Contatti</div>
                    <a href="mailto:info@torollercollective.it" class="footer-link">info@torollercollective.it</a>
                    <a href="tel:+390123456789" class="footer-link">+39 0123 456789</a>
                    <a href="#" class="footer-link">Torino, Italia</a>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <div class="footer-copyright">© <?php echo date("Y"); ?> TorollerCollective. Tutti i diritti riservati.</div>
            
            <div class="footer-social">
                <a href="https://www.facebook.com/share/195xtDc71D/?mibextid=wwXIfr" class="footer-social-icon" target="_blank">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/>
                    </svg>
                </a>
                <a href="https://www.instagram.com/torollercollective?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw==" class="footer-social-icon"target="_blank">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z"/>
                    </svg>
                </a>
              
            </div>
        </div>
    </div>
</body>
</html>
