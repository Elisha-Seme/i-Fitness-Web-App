<?php
// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>i-Fitness</title>
    <!-- Bootstrap CSS link -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Your custom CSS file -->
    <link rel="stylesheet" href="style.css">
    <!-- Font Awesome CDN for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="icon" type="image/png" href="logo.png">
    <!-- Bootstrap JavaScript and dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <style>
        /* CSS to make the navbar static */
        body {
            padding-top: 60px; /* Adjust this value to match the navbar height */
        }

        header {
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand img {
            max-height: 40px; /* Adjust the maximum height of the logo */
        }

        .navbar {
            min-height: 60px; /* Adjust the height of the header bar */
        }

        /* Navbar links hover effect */
        .navbar-nav .nav-item .nav-link {
            transition: color 0.3s;
        }

        .navbar-nav .nav-item .nav-link:hover {
            color: #c9a335; /* Change color on hover to goldish */
        }

        /* Active link style */
        .navbar-nav .nav-item.active .nav-link {
            font-weight: bold; /* Example: Make active link bold */
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <!-- Logo section -->
            <a class="navbar-brand" href="#"><img src="logo.png" alt="i-Fitness Logo"></a>
            <!-- End Logo section -->
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item active">
                        <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard <i class="fas fa-running"></i></a>
                    </li> 

                    <!-- Add more navigation links here -->
                </ul>
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <span class="navbar-text mr-3">
                            <i class="fas fa-user"></i> Welcome, <?php echo strtoupper($_SESSION['username']); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>
    <!-- Your page content starts here -->

     <div class="container mt-5"> <!-- Add top margin to create space below header -->
        <div class="row text-center">
            <div class="col-4 col-md-2 mb-4"> <!-- Add margin bottom for spacing between icons -->
                <i class="fas fa-dumbbell fa-lg"></i><br>
                Gym
            </div>
            <div class="col-4 col-md-2 mb-4">
                <i class="fas fa-running fa-lg"></i><br>
                Running
            </div>
            <div class="col-4 col-md-2 mb-4">
                <i class="fas fa-bicycle fa-lg"></i><br>
                Cycling
            </div>


        </div>
    </div>



    <!-- Bootstrap JavaScript and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
