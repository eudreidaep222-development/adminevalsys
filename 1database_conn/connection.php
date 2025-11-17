<?php
$serverName = "localhost";
$userName = "root";
$password = "";
$dbName = "adminevalsys";

$conn = mysqli_connect($serverName, $userName, $password, $dbName);

if (!$conn) { 
    die("Failed to connect to database: " . mysqli_connect_error());
}
?>
