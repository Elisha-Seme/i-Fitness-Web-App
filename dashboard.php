<?php
// Start the session at the very beginning
session_start();
//error_reporting(0);

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

require_once "header.php";
require_once "database.php";
include 'strava_auth.php';
error_reporting(0);

// Assign username from session
$username = $_SESSION['username'] ?? null;

// Check if the username is set
if (!$username) {
    $error_banner = "Error: Username not found in session.";
}

// Initialize metrics
$total_distance_today = 0;
$total_distance_last_7_days = 0;
$total_time_today = 0;
$total_time_last_7_days = 0;

// Get the current date
$current_date = date('Y-m-d');

// Fetch the user_id based on the username from the session
$user_query = "SELECT user_id FROM users WHERE username = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("s", $username);
$stmt->execute();
$user_result = $stmt->get_result();

// Check if the user_id query was successful
if ($user_result && $user_result->num_rows > 0) {
    $row = $user_result->fetch_assoc();
    $user_id = $row['user_id'];

    // Update activity_data table by fetching data from Strava
    $accessToken = $_SESSION['access_token'];
    $url = 'https://www.strava.com/api/v3/athlete/activities';

    // Make an API call to Strava to fetch data
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $accessToken
    ));
    $response = curl_exec($ch);
    curl_close($ch);
    // Debug: Print response
//echo "Response from Strava API: <pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
    //echo "Session: " . print_r($_SESSION, true); 
// Debug: Print response
//echo "Response from Strava API: <pre>" . $response . "</pre>";

    // Check for errors in the response
    if ($response === false) {
        $error_banner = "Error fetching data from Strava.";
    } else {
        // Decode JSON response
        $activities = json_decode($response, true);

        // Check if there are any activities
        if (!empty($activities)) {
            // Calculate metrics using Strava data
            foreach ($activities as $activity) {
                $start_date = date('Y-m-d', strtotime($activity['start_date']));
                $distance = $activity['distance'] / 1000; // Convert meters to kilometers
                $time_seconds = $activity['moving_time'] ?? 0; // Time in seconds, default to 0 if not present

                // Calculate total distance for today
                if ($start_date === $current_date) {
                    $total_distance_today += $distance; // Add to total distance today
                    $total_time_today += $time_seconds / 60; // Add to total time today (convert seconds to minutes)
                }

                // Calculate total distance for the last 7 days
                $seven_days_ago = date('Y-m-d', strtotime('-7 days'));
                if ($start_date >= $seven_days_ago && $start_date <= $current_date) {
                    $total_distance_last_7_days += $distance; // Add to total distance last 7 days
                    $total_time_last_7_days += $time_seconds / 60; // Add to total time last 7 days (convert seconds to minutes)
                }

                // Insert or update activity into the activity_data table
                // (Code for database operations goes here)
            }
        }

        // Display success message
        $success_banner = "Activities updated successfully.";
    }
} else {
    // User not found
    header('Location: login.php');
    $error_banner = "User not found.";
    exit();
}


// Retrieve current year
$current_year = date('Y');

// Initialize variables for success and error messages
$success_message = $error_message = '';

// Retrieve the end date of the last set goal
$get_last_goal_end_date_query = "SELECT end_date FROM user_goals WHERE user_id = ? ORDER BY end_date DESC LIMIT 1";
$stmt = $conn->prepare($get_last_goal_end_date_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$last_goal_data = $result->fetch_assoc();
$last_goal_end_date = $last_goal_data ? $last_goal_data['end_date'] : null;

// If there are no previous goals or the last goal has already ended, set the minimum date to today
$min_date = $last_goal_end_date ? date('Y-m-d', strtotime('+1 day', strtotime($last_goal_end_date))) : date('Y-m-d');

// Retrieve form data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $goal_type = $_POST['goalType'];
    $target_value = $_POST['targetValue'];
    $start_date = $_POST['startDate'];
    $end_date = $_POST['endDate'];

    // Check if start and end date are at least 30 days apart and not already taken by another goal
    if ((strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24) < 30) {
        $error_message = "Invalid date range. Goals must be set for at least 30 days.";
    } elseif ($last_goal_end_date && strtotime($start_date) <= strtotime($last_goal_end_date)) {
        $error_message = "Invalid start date. Please choose a date after the end date of your last goal.";
    } elseif (($goal_type === "Distance" && $target_value < 1) || ($goal_type === "Time" && $target_value < 5)) {
        // Check if the target value meets the minimum requirement for distance or time goals
        $error_message = $goal_type === "Distance" ? "Minimum distance target is 2 Km." : "Minimum time target is 30 mins.";
    } else {
        // Insert new goal into database
        $insert_goal_query = "INSERT INTO user_goals (user_id, goal_type, target_value, start_date, end_date, progress) VALUES (?, ?, ?, ?, ?, 0)";
        $stmt = $conn->prepare($insert_goal_query);
        $stmt->bind_param("issss", $user_id, $goal_type, $target_value, $start_date, $end_date);

        // Execute the query
        if ($stmt->execute()) {
            // Goal set successfully
            $success_message = "Goal set successfully.";
        } else {
            // Error occurred
            $error_message = "Error: Unable to set goal. Please try again.";
        }
    }
}
?>


<!-- HTML code for the dashboard -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- Include Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Include custom CSS file -->
    <link rel="stylesheet" href="style.css">
    
    <style>
        body {
    background-color: #004d40;
    color: #b8b8b8;
}

.card {
    background: linear-gradient(145deg, #004d40, #005d50); /* Gradient background */
    border: 1px solid #b8b8b8;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3); /* More intense shadow */
    border-radius: 12px; /* More rounded corners */
    padding: 20px; /* Internal padding */
    margin: 15px; /* External margin */
    position: relative; /* For inner border positioning */
    overflow: hidden; /* Ensure inner elements don't spill out */
    transition: transform 0.3s, box-shadow 0.3s; /* Smooth transition for hover effect */
}

.card::before {
    content: '';
    position: absolute;
    top: -2px;
    bottom: -2px;
    left: -2px;
    right: -2px;
    border: 2px solid rgba(200, 163, 53, 0.3); /* Inner border with goldish color */
    border-radius: 12px; /* Match card border-radius */
    pointer-events: none; /* Ensure it's not interactive */
}

.card:hover {
    transform: translateY(-10px); /* More noticeable move up on hover */
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.4); /* Even darker shadow on hover */
}

.card-title {
    color: #c9a335; /* Ensure title color is consistent */
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2); /* Subtle text shadow */
}


.card-title {
    color: #c9a335;
}

.alert-success {
    color: #155724;
    background-color: #d4edda;
    border-color: #c3e6cb;
}

.alert-danger {
    color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;
}

footer {
    background-color: #004d40;
    color: #ffffff;
}

.btn-primary {
    background-color: #004d40;
    border-color: #c9a335;
    color: #c9a335;
}
.btn-primary:hover {
    background-color: #004d40;
    color: #b8b8b8 !important; /* Royal Grey on hover */
}

.btn-success {
    background-color: #155724;
    border-color: #155724;
}

.btn-info {
    background-color: #b8b8b8;
    border-color: #b8b8b8;
    color: #004d40;
}

.modal-content {
    background-color: #004d40;
    color: #b8b8b8;
}

.table {
    color: #b8b8b8; /* Greyish text color */
    border-color: #c9a335; /* Goldish border color */
}

.table th {
    color: #b8b8b8; /* Goldish heading color */
}

.table thead th {
    border-bottom: 2px; /* Goldish bottom border for header */
}

.table tbody td {
    border-top: 1px solid; /* Goldish top border for rows */
}




.table tbody tr:hover {
    background-color: #005d50; /* Slightly darker green on row hover */
} solid #c9a335

    </style>
</head>

<body>
<div class="container">
    <!-- Error banner -->
    <?php if (isset($error_banner)) { ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_banner; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php } ?>

    <!-- Success banner -->
    <?php if (isset($success_banner)) { ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_banner; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php } ?>

    <!-- Display metrics -->
    <h5>Your Metrics</h5>
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Distance Today</h5>
                    <p class="card-text"><?php echo number_format($total_distance_today, 2); ?> km</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Time Today</h5>
                    <p class="card-text"><?php echo number_format($total_time_today, 2); ?> mins</p>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Distance Last 7 Days</h5>
                    <p class="card-text"><?php echo number_format($total_distance_last_7_days, 2); ?> km</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Time Last 7 Days</h5>
                    <p class="card-text"><?php echo number_format($total_time_last_7_days, 2); ?> mins</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Today's Progress</h5>
                    <div class="progress">
                        <?php
                        // Check if there is a goal set for the current month
                        $goal_type = "N/A";
                        $current_month = date('M');
                        $fetch_goal_query = "SELECT target_value, goal_type FROM user_goals WHERE user_id = ? AND MONTH(start_date) = MONTH(CURRENT_DATE())";
                        $stmt = $conn->prepare($fetch_goal_query);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $goal_result = $stmt->get_result();

                        if ($goal_result->num_rows > 0) {
                            $goal_row = $goal_result->fetch_assoc();
                            $target_value = $goal_row['target_value'];
                            $goal_type = $goal_row['goal_type'];

                            // Calculate completion percentage
                            $completion_percentage = 0;
                            if ($goal_type === 'Distance') {
                                $completion_percentage = ($total_distance_today / $target_value) * 100;
                                $distance_left = ($target_value - $total_distance_today);
                                $percentage_left = ($distance_left / $target_value) * 100;
                            } elseif ($goal_type === 'Time') {
                                $completion_percentage = ($total_time_today / $target_value) * 100;
                                $time_left = $target_value - $total_time_today;
                                $percentage_left = ($time_left / $target_value) * 100;
                            }

                            // Progress bar colors based on completion percentage
                            $progress_color = ($completion_percentage >= 100) ? 'bg-success' : 'bg-info';

                            // Display progress bar
                            echo '<div class="progress-bar ' . $progress_color . '" role="progressbar" style="width: ' . $completion_percentage . '%" aria-valuenow="' . $completion_percentage . '" aria-valuemin="0" aria-valuemax="100">';
                            if ($completion_percentage > 0) {
                                echo '<span style="font-size: 12px; padding: 0; color: black;">Total Progress Today: ' . number_format($completion_percentage, 2) . '% | ';
                                if ($goal_type === 'Distance') {
                                    echo 'Distance Left: ' . number_format($distance_left, 2) . ' Km (' . number_format($percentage_left, 2) . '%) | ';
                                } elseif ($goal_type === 'Time') {
                                    $mins = floor($time_left / 60);
                                    $secs = $time_left % 60;
                                    echo 'Time Left: ' . $mins . ' mins ' . $secs . ' sec (' . number_format($percentage_left, 2) . '%) | ';
                                }
                                echo '</span>';
                            } else {
                                echo '<span style="font-size: 12px; padding: 0; color: black;">No progress yet.</span>';
                            }
                            echo '</div>';
                        } else {
                            echo '<div class="alert alert-warning" role="alert">No goal set for the current month.</div>';
                        }
                        ?>
                        
                    </div>
      <div class="row mt-4">
        <div class="col-md-6">
            <?php
            if ($goal_type === 'Distance') {
                echo '<p>Total Distance Today: ' . number_format($total_distance_today, 2) . 'Km</p>';
                echo '<p>Total Distance Last 7 Days: ' . number_format($total_distance_last_7_days, 2) . 'Km</p>';
                echo '<p>Total Time Today: ' . number_format($total_time_today, 2) . 'Mins</p>';
            } elseif ($goal_type === 'Time') {
                echo '<p>Total Time Today: ' . gmdate("i:s", $total_time_today) . '</p>';
                echo '<p>Total Time Last 7 Days: ' . gmdate("i:s", $total_time_last_7_days) . '</p>';
                echo '<p>Total Distance Today: ' . number_format($total_distance_today, 2) . 'Km</p>';
            }
            ?>
        </div>
    </div>

                </div>
            </div>
        </div>
        

        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">M-Pesa Section</h5>
                    <p class="card-text">Balance: <?php echo number_format($user_balance, 2); ?> Ksh</p>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#topUpModal">Top Up</button>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#withdrawModal">Withdraw</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Up Modal -->
    <div class="modal fade" id="topUpModal" tabindex="-1" aria-labelledby="topUpModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="topUpModalLabel">Top Up Balance</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="topUpForm" method="POST" action="process_top_up.php">
                        <div class="form-group">
                            <label for="mpesaNumber">M-Pesa Number</label>
                            <input type="tel" class="form-control" id="mpesaNumber" name="mpesa_number" required>
                        </div>
                        <div class="form-group">
                            <label for="topUpAmount">Amount (Ksh)</label>
                            <input type="number" class="form-control" id="topUpAmount" name="amount" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Top Up</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Withdraw Modal -->
    <div class="modal fade" id="withdrawModal" tabindex="-1" aria-labelledby="withdrawModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="withdrawModalLabel">Withdraw Balance</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="withdrawForm" method="POST" action="process_withdraw.php">
                        <div class="form-group">
                            <label for="mpesaNumber">M-Pesa Number</label>
                            <input type="tel" class="form-control" id="mpesaNumber" name="mpesa_number" required>
                        </div>
                        <div class="form-group">
                            <label for="withdrawAmount">Amount (Ksh)</label>
                            <input type="number" class="form-control" id="withdrawAmount" name="amount" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Withdraw</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<!-- Table for displaying user goals -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Your Goals</h5>
                <div style="max-height: 200px; overflow-y: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Month</th>
                            <th scope="col">Goal Type</th>
                            <th scope="col">Target Value</th>
                            <th scope="col">Start Date</th>
                            <th scope="col">End Date</th>
                        </tr>
                    </thead>
                    
                    <tbody>
                        <?php
                        // Fetch user's goals from the database
                        $fetch_goals_query = "SELECT MONTHNAME(start_date) AS month, goal_type, target_value, start_date, end_date FROM user_goals WHERE user_id = ?";
                        $stmt = $conn->prepare($fetch_goals_query);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $goals_result = $stmt->get_result();

                        if ($goals_result->num_rows > 0) {
                            while ($goal_row = $goals_result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $goal_row['month'] . "</td>";
                                echo "<td>" . $goal_row['goal_type'] . "</td>";
                                echo "<td>" . number_format($goal_row['target_value'], 2) . "</td>";
                                echo "<td>" . date('d-M-Y', strtotime($goal_row['start_date'])) . "</td>";
                                echo "<td>" . date('d-M-Y', strtotime($goal_row['end_date'])) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5'>No goals found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
                </div>
                <!-- Button trigger modal -->
                <button type="button" class="btn btn-primary mt-3" data-toggle="modal" data-target="#addGoalModal">
                    Add New Goal
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Goal Modal -->
<div class="modal fade" id="addGoalModal" tabindex="-1" aria-labelledby="addGoalModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addGoalModalLabel">Add New Goal</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Form for adding goals -->
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label for="goalType">Goal Type:</label>
                        <select class="form-control" id="goalType" name="goalType">
                            <option value="Distance">Distance</option>
                            <option value="Time">Time</option>
                            <option value="Calories">Calories</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="targetValue">Target Value:</label>
                        <input type="number" class="form-control" id="targetValue" name="targetValue" required>
                    </div>
                    <div class="form-group">
                        <label for="startDate">Start Date:</label>
                        <input type="date" class="form-control" id="startDate" name="startDate" required>
                    </div>
                    <div class="form-group">
                        <label for="endDate">End Date:</label>
                        <input type="date" class="form-control" id="endDate" name="endDate" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Set Goal</button>
                </form>
                <!-- End form -->
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="text-center py-4">
    <p>&copy; <?php echo date("Y"); ?> Fitness Dashboard. All rights reserved.</p>
</footer>

    <script>
        // Get the current year
        var currentYear = new Date().getFullYear();

        // Update the content of the span element with id "currentYear"
        document.getElementById('currentYear').textContent = currentYear;
    </script>


<!-- Include Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<!-- Include custom JS file -->
<script src="dashboard.js"></script>
<script>
    // Auto close the success message after 4 seconds
    setTimeout(function() {
        document.querySelector('.alert-success').style.display = 'none';
    }, 4000);

    // Auto close the error message after 4 seconds
    setTimeout(function() {
        document.querySelector('.alert-danger').style.display = 'none';
    }, 4000);
</script>
</body>
</html>
