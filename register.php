<?php
// Include Strava authentication credentials
include 'strava_auth.php';

// Function to log API responses
function logResponse($response) {
    // Initialize error log file
    $errorLog = 'error.txt';
    // Log the response
    file_put_contents($errorLog, "[" . date('Y-m-d H:i:s') . "] API RESPONSE: $response" . PHP_EOL, FILE_APPEND);
}

// Function to log errors
function logError($errorMessage) {
    // Initialize error log file
    $errorLog = 'error.txt';
    // Log the error message
    file_put_contents($errorLog, "[" . date('Y-m-d H:i:s') . "] ERROR: $errorMessage" . PHP_EOL, FILE_APPEND);
}

// Check if the user is authenticated
if (!isset($_SESSION['access_token'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}

// Fetch user data from Strava's API
$apiUrl = 'https://www.strava.com/api/v3/athlete';
$accessToken = $_SESSION['access_token'];

// Make a GET request to Strava's API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Authorization: Bearer ' . $accessToken
));
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Log the Strava API response
logResponse($response);

// Check if request was successful
if ($httpCode == 200) {
    // Parse JSON response
    $userData = json_decode($response, true);

    // Check if user data is fetched successfully
    if ($userData && isset($userData['username'])) {
        // Pre-fill registration form with user's username
        $username = $userData['username'];
        
    } else {
        // Error: User data missing or invalid
        logError("Error: User data missing or invalid");
        echo "Error: User data missing or invalid";
    }
} else {
    // Error: HTTP request failed
    logError("Error: HTTP request failed with status code $httpCode");
    echo "Error: HTTP request failed. Please check error log for details.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Custom CSS -->
    <style>
        /* Custom styles to match index and dashboard */
        body {
            background-color: #004d40; /* Dark green background */
            color: #b8b8b8; /* Royal grey text color */
        }
        .container {
            margin-top: 50px; /* Adjust margin from top */
            max-width: 400px; /* Limit container width */
        }
        .form-group {
            margin-bottom: 15px; /* Reduce margin between form groups */
        }
        .form-control {
            padding: 10px; /* Increase padding for form controls */
            font-size: 16px; /* Increase font size */
            background-color: #004d40; /* Dark green form background */
            border-color: #b8b8b8; /* Light border color */
            color: #b8b8b8; /* Royal grey text color */
        }
        .btn {
            padding: 10px 20px; /* Increase padding for buttons */
            font-size: 16px; /* Increase font size */
        }
        .btn-primary {
            background-color: #c9a335; /* Goldish button color */
            border-color: #c9a335; /* Goldish border color */
        }
        .btn-primary:hover {
            background-color: #b88f0f; /* Darker goldish hover color */
            border-color: #b88f0f; /* Darker goldish hover border color */
        }
        a {
            color: #c9a335; /* Goldish link color */
        }
        a:hover {
            color: #b88f0f; /* Darker goldish hover link color */
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center mb-4">Register</h2>
        <form action="register_process.php" method="post">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : ''; ?>" required>
            </div>
            <input type="hidden" name="access_token" value="<?php echo isset($_SESSION['access_token']) ? htmlspecialchars($_SESSION['access_token']) : ''; ?>">
            <input type="hidden" name="refresh_token" value="<?php echo isset($_SESSION['refresh_token']) ? htmlspecialchars($_SESSION['refresh_token']) : ''; ?>">

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Register</button>
        </form>
        <p class="mt-3 text-center">Already have an account? <a href="login.php">Login Here</a></p>
    </div>
    <footer class="py-4">
        <div class="container text-center">
            <p>&copy; <span id="currentYear"></span> i-Fitness. All rights reserved.</p>
        </div>
    </footer>
    <!-- Bootstrap JS (optional, for certain components) -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

