<?php
function fetchAndStoreTokens($conn, $username) {
    // Strava OAuth 2.0 configuration
    $client_id = '126078'; // Your Strava client ID
    $client_secret = '499c4b64aacbf533b83308db9c916c3e4c3e3ea0'; // Your Strava client secret
    $redirect_uri = 'http://localhost/ifitness/strava_callback.php'; // Your callback URL

    // Hardcoded refresh token
    $refresh_token = 'b900aec41aee1ded5097b8e67c1b9a0d5d0c624e';

    // Prepare parameters for token refresh request
    $params = array(
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'refresh_token' => $refresh_token,
        'grant_type' => 'refresh_token'
    );

    // Make a POST request to the token endpoint
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://www.strava.com/oauth/token');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    // Parse the response
    $data = json_decode($response, true);

    // Store the new access token in the session
    if (isset($data['access_token'])) {
        $_SESSION['strava_access_token'] = $data['access_token'];
    } else {
        // Handle error (you might want to log the error message from $data)
        // For example, redirect for reauthorization
        header("Location: https://www.strava.com/oauth/authorize?client_id=" . $client_id . "&redirect_uri=" . urlencode($redirect_uri) . "&response_type=code&scope=activity:read_all");
        exit;
    }
}
?>
