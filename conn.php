<?php 
function connetti($db){
    $server = getenv('DB_HOST') ?: 'localhost';
    $user = getenv('DB_USERNAME') ?: 'root';
    $password = getenv('DB_PASSWORD') ?: '';
    $database = getenv('DB_DATABASE') ?: 'toroller';

    $conn = mysqli_connect($server, $user, $password, $database);
    
    if (!$conn) {
        die("Connessione fallita: " . mysqli_connect_error());
    }
    
    return $conn;
}
?>