<?php
session_start();
require_once "database.php";

// Strava OAuth 2.0 configuration
$client_id = '126078'; // Your Strava client ID
$client_secret = '499c4b64aacbf533b83308db9c916c3e4c3e3ea0'; // Your Strava client secret
$redirect_uri = 'https://i-fitness-antler-pitch.amiruhamachurchwarabamedicalcentre.com/strava_callback.php'; // Your callback URL

// Initialize error log file
$errorLog = 'error.txt';

// Check if the authorization code is provided in the query parameters
if (isset($_GET['code'])) {
    // Extract the authorization code from the query parameters
    $code = $_GET['code'];

    // Exchange the authorization code for an access token
    $tokenUrl = 'https://www.strava.com/oauth/token';
    $postData = array(
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'code' => $code,
        'grant_type' => 'authorization_code'
    );

    // Initialize curl
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tokenUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute curl and get response
    $response = curl_exec($ch);
    $responseData = json_decode($response, true);

    // Check for errors
    if (isset($responseData['access_token'])) {
        // Access token and refresh token retrieved successfully
        $accessToken = $responseData['access_token'];
        $refreshToken = $responseData['refresh_token'];

        // Log the access token
        logMessage("Access token: $accessToken");

        // Store the tokens in the session
        $_SESSION['username'] = $_SESSION['username']; // Assuming username is set in the session
        $_SESSION['access_token'] = $accessToken;
        $_SESSION['refresh_token'] = $refreshToken;

        // Store the tokens in the database
        $username = $_SESSION['username'];
        $sql = "UPDATE users SET access_token = ?, refresh_token = ? WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $accessToken, $refreshToken, $username);
        $stmt->execute();
        
        // Redirect to registration page or any other page as needed
        header("Location: register.php");
        exit();
    } elseif (isset($responseData['error'])) {
        // Error retrieving access token
        $errorMessage = $responseData['error'];
        logError("Error retrieving access token: $errorMessage");
        echo "Error retrieving access token. Please check error log for details.";
        exit();
    } else {
        // Unexpected response from Strava API
        logError("Unexpected response from Strava API: " . print_r($responseData, true));
        echo "Unexpected response from Strava API. Please check error log for details.";
        exit();
    }

    // Close curl
    curl_close($ch);
} else {
    // Handle cases where the authorization code is not provided
    logError("Error: Authorization code not provided.");
    echo "Error: Authorization code not provided.";
    exit();
}

// Function to log messages
function logMessage($message) {
    global $errorLog;
    file_put_contents($errorLog, date('[Y-m-d H:i:s] ') . 'MESSAGE: ' . $message . PHP_EOL, FILE_APPEND);
}

// Function to log errors
function logError($error) {
    global $errorLog;
    file_put_contents($errorLog, date('[Y-m-d H:i:s] ') . 'ERROR: ' . $error . PHP_EOL, FILE_APPEND);
}
?>
