






// Function to fetch data from Strava API and update the database
function fetchStravaDataAndUpdateDatabase() {
    // Get the access token from the hidden input field
    const accessToken = document.getElementById('accessToken').value;

    // Make an API call to Strava to fetch data
    fetch('https://www.strava.com/api/v3/athlete/activities', {
        method: 'GET',
        headers: {
            'Authorization': accessToken // Use the access token obtained after authentication
        }
    })
    .then(response => response.json())
    .then(data => {
        // Process the fetched data and update the database
        data.forEach(activity => {
            const activityType = activity.type;
            const distance = activity.distance;
            const duration = activity.moving_time;
            const startTime = activity.start_date;

            // Send the data to your server to update the database
            updateDatabase(activityType, distance, duration, startTime);
        });
    })
    .catch(error => {
        console.error('Error fetching data from Strava:', error);
    });
}

// Function to update the database with fetched data
function updateDatabase(activityType, distance, duration, startTime) {
    // Send an AJAX request to your server to update the database
    // Example using fetch API:
    fetch('update_database.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            activityType: activityType,
            distance: distance,
            duration: duration,
            startTime: startTime
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Database updated successfully:', data);
    })
    .catch(error => {
        console.error('Error updating database:', error);
    });
}

// Call the fetchStravaDataAndUpdateDatabase function every 10 minutes
setInterval(fetchStravaDataAndUpdateDatabase, 60000); // 10 minutes interval


window.onerror = function(message, source, lineno, colno, error) {
    // Send error details to the server
    const errorData = {
        message: message,
        source: source,
        lineno: lineno,
        colno: colno,
        error: error ? error.stack : null
    };

    // Send error data to server
    sendErrorToServer(errorData);
};

function sendErrorToServer(errorData) {
    // Send error data to the server using an AJAX request
    fetch('log_errors.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(errorData)
    }).then(response => {
        // Handle response from the server if needed
    }).catch(error => {
        console.error('Error sending error data to server:', error);
    });
}
