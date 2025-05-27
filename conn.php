<?php 
function connetti($db_name) {
    $server = 'localhost';
    $user = 'root';
    $password = '';
    $database = $db_name;

    $conn = mysqli_connect($server, $user, $password, $database);
    
    if (!$conn) {
        die("Connessione fallita: " . mysqli_connect_error());
    }
    return $conn;
}
?>


