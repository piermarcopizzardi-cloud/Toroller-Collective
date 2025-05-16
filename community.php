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
    <title>TorollerCollective - Forum della Community</title>
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
            background-color: #f5f5f5;
        }
        
        .header {
            width: 100%;
            height: 118px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-left: 110px;
            padding-right: 110px;
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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
            text-decoration: none;
        }
        
        .nav-link.active {
            color: #04CD00;
            font-weight: 600;
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

        .forum-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .forum-header {
            text-align: center;
            margin-bottom: 60px;
            background: #ffffff;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .forum-title {
            color: #04CD00;
            font-size: 42px;
            font-weight: 800;
            margin-bottom: 20px;
        }

        .forum-description {
            color: #6B7280;
            font-size: 20px;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
        }

        .forum-categories {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .category-card {
            background: #ffffff;
            border: 1px solid #E5E7EB;
            border-radius: 20px;
            padding: 30px;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            display: block;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }

        .category-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: #04CD00;
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .category-card:hover::before {
            transform: scaleX(1);
        }

        .category-title {
            color: #04CD00;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .category-description {
            color: #6B7280;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .topic-list {
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .topic-item {
            padding: 25px;
            border-bottom: 1px solid #E5E7EB;
            display: block;
            text-decoration: none;
            transition: all 0.3s ease;
            background: #ffffff;
        }

        .topic-item:hover {
            background: #F9FAFB;
        }

        .topic-info {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
        }

        .topic-main {
            flex: 1;
        }

        .topic-title {
            color: #111827;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
            text-decoration: none;
            display: block;
        }

        .topic-title:hover {
            color: #04CD00;
        }

        .topic-meta {
            color: #6B7280;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .topic-author {
            color: #04CD00;
            font-weight: 600;
        }

        .topic-stats {
            background: #F3F4F6;
            padding: 8px 16px;
            border-radius: 30px;
            color: #6B7280;
            font-size: 14px;
            font-weight: 500;
        }

        .create-topic-btn {
            background: #04CD00;
            color: #ffffff;
            padding: 15px 30px;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }

        .create-topic-btn:hover {
            background: #03b100;
            transform: translateY(-2px);
        }

        .create-topic-form,
        .create-reply-form {
            background: #ffffff;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            color: #374151;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .form-input,
        .form-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-input:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #04CD00;
        }

        .form-textarea {
            min-height: 150px;
            resize: vertical;
        }

        .form-submit {
            background: #04CD00;
            color: #ffffff;
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .form-submit:hover {
            background: #03b100;
            transform: translateY(-2px);
        }

        .login-prompt {
            text-align: center;
            padding: 60px;
            background: #ffffff;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .login-prompt p {
            color: #374151;
            font-size: 20px;
            margin-bottom: 25px;
        }

        .breadcrumb {
            display: flex;
            gap: 12px;
            margin-bottom: 30px;
            padding: 20px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .breadcrumb a {
            color: #04CD00;
            text-decoration: none;
            font-weight: 500;
        }

        .breadcrumb span {
            color: #9CA3AF;
        }

        .topic-content {
            background: #ffffff;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .topic-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #E5E7EB;
        }

        .topic-body {
            color: #374151;
            font-size: 16px;
            line-height: 1.8;
            margin-bottom: 30px;
        }

        .reply-list {
            margin-top: 30px;
        }

        .reply-item {
            background: #F9FAFB;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #04CD00;
        }

        .reply-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .reply-author {
            color: #04CD00;
            font-weight: 600;
            font-size: 16px;
        }

        .reply-date {
            color: #6B7280;
            font-size: 14px;
        }

        .reply-content {
            color: #374151;
            font-size: 16px;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .header {
                padding-left: 20px;
                padding-right: 20px;
            }

            .forum-categories {
                grid-template-columns: 1fr;
            }

            .topic-info {
                flex-direction: column;
            }

            .topic-stats {
                align-self: flex-start;
            }

            .breadcrumb {
                flex-wrap: wrap;
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
                <a href="index.php" class="nav-link">Home</a>
                <a href="community.php" class="nav-link active">Community</a>
                <a href="shop.php" class="nav-link">Shop</a>
                <a href="eventi.php" class="nav-link">Eventi</a>
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
</body>
</html>
