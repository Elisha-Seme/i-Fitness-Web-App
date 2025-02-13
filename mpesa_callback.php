<?php
session_start();
// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}
require_once 'database.php';
require_once 'mpesa_config.php';

// Getting the JSON data from the callback
$data = file_get_contents('php://input');
$response = json_decode($data, true);

// Log the raw response for debugging
file_put_contents('logs/mpesa_callback_raw.log', $data . PHP_EOL, FILE_APPEND);

// Printing the response to a log file for detailed inspection
file_put_contents('logs/mpesa_callback_details.log', var_export($response, true) . PHP_EOL, FILE_APPEND);

if (isset($response['Body']['stkCallback'])) {
    $stkCallback = $response['Body']['stkCallback'];
    $resultCode = $stkCallback['ResultCode'];
    $resultDesc = $stkCallback['ResultDesc'];
    $merchantRequestID = $stkCallback['MerchantRequestID'];
    $checkoutRequestID = $stkCallback['CheckoutRequestID'];

    // Logging detailed response for debugging
    file_put_contents('logs/mpesa_callback.log', print_r($stkCallback, true), FILE_APPEND);

    if ($resultCode == 0) {
        // Payment was successful
        $callbackMetadata = $stkCallback['CallbackMetadata']['Item'];
        $amount = 0;
        $mpesaReceiptNumber = '';
        $transactionDate = '';
        $phoneNumber = '';

        foreach ($callbackMetadata as $item) {
            switch ($item['Name']) {
                case 'Amount':
                    $amount = $item['Value'];
                    break;
                case 'MpesaReceiptNumber':
                    $mpesaReceiptNumber = $item['Value'];
                    break;
                case 'TransactionDate':
                    $transactionDate = date('Y-m-d H:i:s', strtotime($item['Value']));
                    break;
                case 'PhoneNumber':
                    $phoneNumber = $item['Value'];
                    break;
            }
        }

        // Updating the user's balance in the database
        $stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE mpesa_number = ?");
        $stmt->bind_param("ds", $amount, $phoneNumber);
        if ($stmt->execute()) {
            // Inserting the transaction into the payment history table
            $stmt = $conn->prepare("INSERT INTO payment_history (user_id, amount, mpesa_receipt_number, transaction_date) VALUES ((SELECT user_id FROM users WHERE mpesa_number = ?), ?, ?, ?)");
            $stmt->bind_param("siss", $phoneNumber, $amount, $mpesaReceiptNumber, $transactionDate);
            if (!$stmt->execute()) {
                file_put_contents('logs/mpesa_errors.log', 'Error inserting payment history: ' . $stmt->error . PHP_EOL, FILE_APPEND);
            }
        } else {
            file_put_contents('logs/mpesa_errors.log', 'Error updating balance: ' . $stmt->error . PHP_EOL, FILE_APPEND);
        }
    } else {
        // Payment failed
        $errorMessage = "Payment failed: " . $resultDesc;
        switch ($resultCode) {
            case 1032:
                $errorMessage = "Payment canceled by user: " . $resultDesc;
                break;
            case 1:
                $errorMessage = "User entered wrong PIN: " . $resultDesc;
                break;
            
        }
        file_put_contents('logs/mpesa_errors.log', $errorMessage . PHP_EOL, FILE_APPEND);
    }
} else {
    // Invalid response
    $errorMessage = "Invalid callback response: " . print_r($response, true);
    file_put_contents('logs/mpesa_errors.log', $errorMessage . PHP_EOL, FILE_APPEND);
}
?>
