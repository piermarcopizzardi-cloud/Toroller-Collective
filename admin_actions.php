<?php
session_start();
include("conn.php");

// Verifica che l'utente sia amministratore
function checkAdmin($conn) {
    if (!isset($_SESSION['email'])) {
        http_response_code(401);
        die(json_encode(['error' => 'Non autorizzato']));
    }
    
    $email = $_SESSION['email'];
    $query = "SELECT amministratore FROM utente WHERE email = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    
    if (!$user || !$user['amministratore']) {
        http_response_code(403);
        die(json_encode(['error' => 'Accesso negato']));
    }
}

// Inizializza la connessione
$conn = connetti("toroller");
if (!$conn) {
    http_response_code(500);
    die(json_encode(['error' => 'Errore di connessione al database']));
}

// Verifica che l'utente sia admin
checkAdmin($conn);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_product':
                $tipologia = mysqli_real_escape_string($conn, $_POST['tipologia']);
                $prezzo = floatval($_POST['prezzo']);
                $quantita = intval($_POST['quantita']);
                $colore = mysqli_real_escape_string($conn, $_POST['colore']);
                $descrizione = mysqli_real_escape_string($conn, $_POST['descrizione']);
                
                if (isset($_FILES['immagine']) && $_FILES['immagine']['error'] === 0) {
                    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                    $filename = $_FILES['immagine']['name'];
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    
                    if (in_array($ext, $allowed)) {
                        $target_dir = "assets/products/";
                        $new_filename = uniqid() . '.' . $ext;
                        $target_path = $target_dir . $new_filename;
                        
                        if (move_uploaded_file($_FILES['immagine']['tmp_name'], $target_path)) {
                            $query = "INSERT INTO prodotti (tipologia, prezzo, quantita, colore, descrizione, immagine) 
                                    VALUES (?, ?, ?, ?, ?, ?)";
                            $stmt = mysqli_prepare($conn, $query);
                            mysqli_stmt_bind_param($stmt, "sdisss", $tipologia, $prezzo, $quantita, $colore, $descrizione, $new_filename);
                            
                            if (mysqli_stmt_execute($stmt)) {
                                $id = mysqli_insert_id($conn);
                                echo json_encode([
                                    'success' => true,
                                    'message' => 'Prodotto aggiunto con successo!',
                                    'product' => [
                                        'id' => $id,
                                        'tipologia' => $tipologia,
                                        'prezzo' => $prezzo,
                                        'quantita' => $quantita,
                                        'colore' => $colore,
                                        'descrizione' => $descrizione,
                                        'immagine' => $new_filename
                                    ]
                                ]);
                            } else {
                                http_response_code(500);
                                echo json_encode(['error' => 'Errore nell\'aggiunta del prodotto']);
                            }
                        } else {
                            http_response_code(500);
                            echo json_encode(['error' => 'Errore nel caricamento dell\'immagine']);
                        }
                    } else {
                        http_response_code(400);
                        echo json_encode(['error' => 'Tipo di file non supportato']);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Immagine richiesta']);
                }
                break;

            case 'delete_product':
                if (isset($_POST['id'])) {
                    $id = intval($_POST['id']);
                    
                    // Prima eliminiamo l'immagine
                    $query = "SELECT immagine FROM prodotti WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "i", $id);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    
                    if ($product = mysqli_fetch_assoc($result)) {
                        $image_path = "assets/products/" . $product['immagine'];
                        if (file_exists($image_path)) {
                            unlink($image_path);
                        }
                    }
                    
                    // Poi eliminiamo il prodotto dal database
                    $query = "DELETE FROM prodotti WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "i", $id);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        echo json_encode([
                            'success' => true,
                            'message' => 'Prodotto eliminato con successo!'
                        ]);
                    } else {
                        http_response_code(500);
                        echo json_encode(['error' => 'Errore nell\'eliminazione del prodotto']);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'ID prodotto mancante']);
                }
                break;

            case 'add_event':
                $titolo = $_POST['titolo'] ?? '';
                $data = $_POST['data'] ?? '';
                $descrizione = $_POST['descrizione'] ?? '';
                $luogo = $_POST['luogo'] ?? '';
                $immagine = null;

                if (empty($titolo) || empty($data) || empty($descrizione) || empty($luogo)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Tutti i campi testuali sono obbligatori']);
                    exit;
                }

                if (isset($_FILES['immagine']) && $_FILES['immagine']['error'] === UPLOAD_ERR_OK) {
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                    $file_extension = strtolower(pathinfo($_FILES['immagine']['name'], PATHINFO_EXTENSION));

                    if (in_array($file_extension, $allowed_extensions)) {
                        $upload_dir = 'assets/events/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        $immagine = uniqid() . '.' . $file_extension;
                        $upload_path = $upload_dir . $immagine;

                        if (!move_uploaded_file($_FILES['immagine']['tmp_name'], $upload_path)) {
                            http_response_code(500);
                            echo json_encode(['error' => 'Errore nel caricamento dell\'immagine.']);
                            exit;
                        }
                    } else {
                        http_response_code(400);
                        echo json_encode(['error' => 'Tipo di file non supportato. Sono ammessi solo JPG, JPEG, PNG, GIF.']);
                        exit;
                    }
                } else if (isset($_FILES['immagine']) && $_FILES['immagine']['error'] !== UPLOAD_ERR_NO_FILE) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Errore nel caricamento dell\'immagine: ' . $_FILES['immagine']['error']]);
                    exit;
                }
                
                $query = "INSERT INTO eventi (titolo, data, descrizione, luogo, immagine) VALUES (?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "sssss", $titolo, $data, $descrizione, $luogo, $immagine);
                
                if (mysqli_stmt_execute($stmt)) {
                    $id = mysqli_insert_id($conn);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Evento aggiunto con successo!',
                        'event' => [
                            'id' => $id,
                            'titolo' => $titolo,
                            'data' => $data,
                            'descrizione' => $descrizione,
                            'luogo' => $luogo,
                            'immagine' => $immagine
                        ]
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Errore nell\'aggiunta dell\'evento']);
                }
                break;

            case 'delete_event':
                if (isset($_POST['id'])) {
                    $id = intval($_POST['id']);
                    
                    // Prima eliminiamo l'immagine se esiste
                    $query_select_image = "SELECT immagine FROM eventi WHERE id = ?";
                    $stmt_select_image = mysqli_prepare($conn, $query_select_image);
                    mysqli_stmt_bind_param($stmt_select_image, "i", $id);
                    mysqli_stmt_execute($stmt_select_image);
                    $result_image = mysqli_stmt_get_result($stmt_select_image);
                    if ($event_data = mysqli_fetch_assoc($result_image)) {
                        if (!empty($event_data['immagine'])) {
                            $image_path = "assets/events/" . $event_data['immagine'];
                            if (file_exists($image_path)) {
                                unlink($image_path);
                            }
                        }
                    }

                    $query = "DELETE FROM eventi WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "i", $id);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        echo json_encode([
                            'success' => true,
                            'message' => 'Evento eliminato con successo!'
                        ]);
                    } else {
                        http_response_code(500);
                        echo json_encode(['error' => 'Errore nell\'eliminazione dell\'evento']);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'ID evento mancante']);
                }
                break;

            default:
                http_response_code(400);
                echo json_encode(['error' => 'Azione non valida']);
                break;
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Azione non specificata']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Metodo non permesso']);
}

mysqli_close($conn);
