<?php 
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the access token is already set in the session
if (!isset($_SESSION['access_token'])) {
    // Access token is not set, redirect to login page or handle accordingly
    header('Location: login.php');
    exit();
}
?>
