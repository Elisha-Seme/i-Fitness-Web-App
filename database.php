<?php
// Database connection parameters
$servername = "gra106";
$username = "jamakeop_i-fitness";
$password = "/*@!Waraba_2024*/";
$database = "jamakeop_fitness_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
