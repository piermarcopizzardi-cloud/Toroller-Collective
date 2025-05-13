<?php 
function connetti($db){
    $server="localhost"; 
    $user="root"; 
    $password=""; 
    $database="toroller"; 

    $conn = mysqli_connect($server, $user, $password, $database);
    
    if (!$conn) {
        die("Connessione fallita: " . mysqli_connect_error());
    }
    
    return $conn;
}
?>