<?php 
$server="localhost"; 
$user="root"; 
$password=""; 
$database="toroller"; 

$connessione=mysqli_connect($server,$user,$password,$database)
or die ("errore di connessione".mysqli_connect_error()); 

?>