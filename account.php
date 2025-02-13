<?php
session_start();
require_once "header.php"; // Include header
require_once "database.php"; // Include database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Retrieve user's profile details from the database
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Process profile picture upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["profilePicture"])) {
    $target_dir = "profile_pictures/";
    $target_file = $target_dir . basename($_FILES["profilePicture"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if image file is a actual image or fake image
    $check = getimagesize($_FILES["profilePicture"]["tmp_name"]);
    if ($check !== false) {
        $uploadOk = 1;
    } else {
        $error_message = "File is not an image.";
        $uploadOk = 0;
    }

    // Check file size
    if ($_FILES["profilePicture"]["size"] > 500000) {
        $error_message = "Sorry, your file is too large.";
        $uploadOk = 0;
    }

    // Allow certain file formats
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
        && $imageFileType != "gif") {
        $error_message = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        $error_message = "Sorry, your file was not uploaded.";
    } else {
        // if everything is ok, try to upload file
        if (move_uploaded_file($_FILES["profilePicture"]["tmp_name"], $target_file)) {
            // Update profile picture path in the database
            $update_query = "UPDATE users SET profile_picture = ? WHERE user_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("si", $target_file, $user_id);
            if ($stmt->execute()) {
                $success_message = "The file " . htmlspecialchars(basename($_FILES["profilePicture"]["name"])) . " has been uploaded.";
                // Update user's profile picture in session
                $_SESSION['profile_picture'] = $target_file;
            } else {
                $error_message = "Sorry, there was an error uploading your file.";
            }
        } else {
            $error_message = "Sorry, there was an error uploading your file.";
        }
    }
}
?>

<div class="container mt-5">
    <h2>Account Management</h2>
    <!-- Display profile details -->
    <div class="row">
        <div class="col-md-6">
            <?php if (isset($success_message)) { ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $success_message; ?>
                </div>
            <?php } ?>
            <?php if (isset($error_message)) { ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error_message; ?>
                </div>
            <?php } ?>
            <h4>Profile Details:</h4>
            <p>Username: <?php echo $user['username']; ?></p>
            <p>Email: <?php echo $user['email']; ?></p>
            <p>Location: <?php echo $user['location']; ?></p>
            <!-- Display profile picture -->
            <img src="<?php echo isset($_SESSION['profile_picture']) ? $_SESSION['profile_picture'] : 'default_profile_picture.jpg'; ?>" alt="Profile Picture" class="img-fluid mb-3">
            <!-- Profile picture upload form -->
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="profilePicture">Upload New Profile Picture:</label>
                    <input type="file" class="form-control-file" id="profilePicture" name="profilePicture">
                </div>
                <button type="submit" class="btn btn-primary">Upload</button>
            </form>
        </div>
    </div>
</div>

<?php require_once "footer.php"; // Include footer ?>
