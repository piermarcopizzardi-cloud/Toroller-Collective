<?php 
function connetti($db_name_param){ // Parameter will now be used
    $server = getenv('DB_HOST') ?: 'localhost';
    $user = getenv('DB_USERNAME') ?: 'root';
    $password = getenv('DB_PASSWORD') ?: '';
    // Use the provided $db_name_param, allowing environment variable to override
    $database_to_use = getenv('DB_DATABASE') ?: $db_name_param;

    error_log("Attempting DB connection with user: " . $user . " to database: " . $database_to_use . " on host: " . $server); 

    $conn = mysqli_connect($server, $user, $password, $database_to_use);
    
    if (!$conn) {
        error_log("DB Connection Failed for user: " . $user . " to database " . $database_to_use . ". Error: " . mysqli_connect_error()); 
        die("Connessione fallita: " . mysqli_connect_error());
    }
    
    return $conn;
}
?>