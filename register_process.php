<?php
session_start();
require_once "database.php";
include 'fetch_store_tokens.php'; // Include fetchAndStoreTokens function

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    $access_token = $_POST["access_token"];
    $refresh_token = $_POST["refresh_token"];

    if (empty($username) || empty($password) || empty($confirm_password)) {
        echo "Please fill in all fields.";
    } elseif ($password != $confirm_password) {
        echo "Passwords do not match.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            echo "Username already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, access_token, refresh_token) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $hashed_password, $access_token, $refresh_token);

            if ($stmt->execute()) {
                // Registration successful, fetch and store Strava tokens
                fetchAndStoreTokens($conn, $username); // Pass $conn variable
                
                // Success message and redirection

                    
                header("Location: login.php");
                exit();
            } else {
                echo "Error registering user.";
            }
        }
    }
} else {
    header("Location: register.php");
    exit();
}
?>
