<?php 
function connetti($db){
    $server = getenv('DB_HOST') ?: 'localhost';
    $user = getenv('DB_USERNAME') ?: 'root';
    $password = getenv('DB_PASSWORD') ?: '';
    $database = getenv('DB_DATABASE') ?: 'toroller';

    error_log("Attempting DB connection with user: " . $user . " to database: " . $database . " on host: " . $server); // Added for debugging
    error_log("Attempting to connect with username: " . $user . " to host: " . $server . " and database: " . $database); // Debugging line

    $conn = mysqli_connect($server, $user, $password, $database);
    
    if (!$conn) {
        error_log("DB Connection Failed for user: " . $user . ". Error: " . mysqli_connect_error()); // Added for debugging
        die("Connessione fallita: " . mysqli_connect_error());
    }
    
    return $conn;
}
?>