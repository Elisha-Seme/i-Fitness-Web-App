<?php
require_once 'mpesa_config.php';

function generate_token() {
    $credentials = base64_encode(CONSUMER_KEY . ':' . CONSUMER_SECRET);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Basic ' . $credentials));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response);
    return $result->access_token;
}
?>
