<?php
session_start();
error_reporting(0);


// Set timezone to Africa/Nairobi
date_default_timezone_set('Africa/Nairobi');

// Configure error logging to a specific file
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/errorslogin.txt');

require_once "database.php";
error_reporting(E_ALL);
// Function to refresh the access token
function refreshAccessToken($refreshToken)
{
    global $conn;

    $clientId = '126078'; // Replace with your actual client ID
    $clientSecret = '499c4b64aacbf533b83308db9c916c3e4c3e3ea0'; // Replace with your actual client secret
    $refreshUrl = 'https://www.strava.com/oauth/token';

    $data = [
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'grant_type' => 'refresh_token',
        'refresh_token' => $refreshToken
    ];

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $refreshUrl);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($httpCode == 200) {
        $responseData = json_decode($response, true);
        $newAccessToken = $responseData['access_token'];
        $newRefreshToken = $responseData['refresh_token'];

        // Update the access token and refresh token in the database
        $query = "UPDATE users SET access_token = ?, refresh_token = ? WHERE username = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sss", $newAccessToken, $newRefreshToken, $_SESSION['username']);
        $result = $stmt->execute();

        if ($result) {
            // Update session variables with new tokens
            $_SESSION['access_token'] = $newAccessToken;
            $_SESSION['refresh_token'] = $newRefreshToken;
            return $newAccessToken;
        } else {
            error_log("Error updating access token and refresh token in the database: " . $stmt->error);
            return false;
        }
    } else {
        // Handle error
        error_log("Error refreshing access token: HTTP code - $httpCode, cURL error - $curlError, Response - $response");
        return false;
    }
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get username and password from the form
    $username = $_POST["username"];
    $password = $_POST["password"];

    // Validate username and password
    $sql = "SELECT username, password, access_token, refresh_token FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($db_username, $db_password, $access_token, $refresh_token);
        $stmt->fetch();

        // Verify password
        if (password_verify($password, $db_password)) {
            // Password is correct

            // Debug: Check initial tokens
            error_log("Initial access token: $access_token");
            error_log("Initial refresh token: $refresh_token");

            // Check if the access token is empty or invalid
            $isAccessTokenValid = !empty($access_token) && isAccessTokenValid($access_token);

            if (!$isAccessTokenValid) {
                // Access token is invalid or empty, try to refresh it
                $newAccessToken = refreshAccessToken($refresh_token);

                if ($newAccessToken) {
                    $access_token = $newAccessToken;

                    // Debug: Check new tokens
                    error_log("New access token: $access_token");
                    error_log("New refresh token: " . $_SESSION['refresh_token']);
                } else {
                    // Handle token refresh failure
                    $error_message = "Error refreshing access token.";
                }
            }

            // Store username, access token, and refresh token in session
            $_SESSION["username"] = $db_username;
            $_SESSION["access_token"] = $access_token;
            $_SESSION["refresh_token"] = $_SESSION['refresh_token'];

            // Debug: Check session tokens
            error_log("Session access token: " . $_SESSION['access_token']);
            error_log("Session refresh token: " . $_SESSION['refresh_token']);

            // Redirect to the dashboard
            echo '<script>window.location.href = "dashboard.php";</script>';
           // header("Location: dashboard.php");
            exit();
        } else {
            // Incorrect password
            $error_message = "Incorrect password. Please try again.";
        }
    } else {
        // Username not found
        $error_message = "Username not found. Please try again.";
    }

    // Close statement
    $stmt->close();
}

// Function to check if the access token is valid
function isAccessTokenValid($accessToken)
{
    $url = 'https://www.strava.com/api/v3/athlete'; // Test API endpoint

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $accessToken
    ));
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Check if the API call was successful
    if ($httpCode == 200) {
        // Access token is valid
        return true;
    } else {
        // Access token is invalid or expired
        error_log("Error validating access token: HTTP code - $httpCode, cURL error - $curlError, Response - $response");
        return false;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login</title>
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
        .alert {
            padding: 12px; /* Increase padding for alerts */
            font-size: 16px; /* Increase font size */
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
        <h2 class="text-center mb-4">User Login</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <?php if (isset($error_message)) { ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error_message; ?>
                </div>
            <?php } ?>
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>
        <p class="mt-3 text-center">Don't have an account? <a href="register_strava.php">Register</a></p>
    </div>
    
    <footer class="py-4">
        <div class="container text-center">
            <p>&copy; <span id="currentYear"></span> i-Fitness. All rights reserved.</p>
        </div>
    </footer>
    <!-- Bootstrap JavaScript and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

