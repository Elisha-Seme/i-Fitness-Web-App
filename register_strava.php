<?php
// Strava OAuth 2.0 configuration
$client_id = '126078'; // Your Strava client ID
$redirect_uri = 'https://i-fitness-antler-pitch.amiruhamachurchwarabamedicalcentre.com/strava_callback.php'; // Your callback URL
$scope = 'read_all,activity:read_all'; // Desired scope for accessing user data

// Construct the authorization URL
$authorization_url = 'https://www.strava.com/oauth/authorize?client_id=' . $client_id . '&response_type=code&redirect_uri=' . urlencode($redirect_uri) . '&scope=' . urlencode($scope);

// Redirect the user to the authorization URL
header('Location: ' . $authorization_url);
exit();
?>
