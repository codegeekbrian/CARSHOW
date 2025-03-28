<?php
// Retrieve POST data from the form submission
$NAME = $_POST['name'];
$EMAIL = $_POST['email'];
$MESSAGE = $_POST['message'];

// Create a new MySQLi connection to the database
$con = new mysqli("localhost", "root", "", "CARSHOW");

// Check if the connection to the database was successful
if ($con->connect_error) {
    // If the connection failed, stop the script and print the error
    die("Connection failed: " . $con->connect_error);
}

// Prepare the SQL query to insert the data into the contacts table
$sql = "INSERT INTO CONTACTS (names, email, messages  ) VALUES ('$NAME', '$EMAIL','$MESSAGE')";

// Execute the SQL query
$result = $con->query(query: $sql);

// Check if the query was successful
if ($result) {
    // If the query was successful, output a JavaScript alert and redirect to 1register.php
    echo "<script>
            alert('Record successfully added');
            window.location.href = 'contact.php';
          </script>";
} else {
    // If there was an error with the query, print the error
    echo "Error: " . $sql . "<br>" . $con->error;
}

// Close the database connection
$con->close();
?>