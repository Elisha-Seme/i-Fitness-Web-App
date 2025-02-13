<?php
// Retrieve error data from the client
$errorData = json_decode(file_get_contents("php://input"), true);

// Write error data to a log file
$logFile = 'error_javascript.txt';
$logMessage = sprintf("[%s] %s in %s (Line %d): %s\n",
    date('Y-m-d H:i:s'),
    $errorData['message'],
    $errorData['source'],
    $errorData['lineno'],
    $errorData['error']
);

file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);

// Send response to the client
echo json_encode(['status' => 'success']);
?>
