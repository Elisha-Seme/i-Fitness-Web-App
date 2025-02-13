<?php
session_start();
require_once 'database.php';
require_once 'mpesa_config.php';
require_once 'generate_token.php';

// Retrieve posted data
$mpesa_number = $_POST['mpesa_number'];
$amount = $_POST['amount'];
$transaction_reference = 'TRX_' . time();

// Prepare STK push data
$data = array(
    "BusinessShortCode" => SHORTCODE,
    "Password" => base64_encode(SHORTCODE . PASSKEY . date("YmdHis")),
    "Timestamp" => date("YmdHis"),
    "TransactionType" => "CustomerPayBillOnline",
    "Amount" => $amount,
    "PartyA" => $mpesa_number,
    "PartyB" => SHORTCODE,
    "PhoneNumber" => $mpesa_number,
    "CallBackURL" => CALLBACK_URL,
    "AccountReference" => "i-Fitness",
    "TransactionDesc" => "Top Up Payment"
);

// Convert the data array to JSON
$json_data = json_encode($data);

// Initialize cURL session
$ch = curl_init('https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest');
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Bearer ' . generate_token()));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);

// Execute cURL session and capture response
$response = curl_exec($ch);
if ($response === false) {
    // Handle cURL error
    file_put_contents('stk_push_errors.log', date('Y-m-d H:i:s') . ': cURL error: ' . curl_error($ch) . PHP_EOL, FILE_APPEND);
    header('Location: error.php');
    exit;
}
curl_close($ch);

// Decode the JSON response
$response_data = json_decode($response, true);

// Check the response code from M-Pesa
if (isset($response_data['ResponseCode']) && $response_data['ResponseCode'] == "0") {
    // Redirect to success page if the STK push was successful
    header('Location: success.php');
    exit;
} else {
    // Log the error response and redirect to error page
    file_put_contents('stk_push_errors.log', date('Y-m-d H:i:s') . ': ' . print_r($response_data, true) . PHP_EOL, FILE_APPEND);
    header('Location: error.php');
    exit;
}
?>
