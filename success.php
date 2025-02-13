<?php 
session_start();

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

require_once "header.php";
require_once "database.php";

error_reporting(0);
 ?>
<!DOCTYPE html>
<html lang="en">
<head>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Initiated</title>
    <!-- Include Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Include custom CSS file -->
    <link rel="stylesheet" href="style.css">
</head>
</head>
<body>
    <h1>Payment Initiated Successfully</h1>
    <p>Your payment request has been sent. Please check your phone to complete the payment.</p>
   <button> <a href="dashboard.php">Go back to Dashboard</a></button>
</body>
</html>
