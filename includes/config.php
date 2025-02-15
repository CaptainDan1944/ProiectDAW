<?php
$servername = "localhost";
$username = "root"; // Default MySQL user in XAMPP
$password = ""; // No password by default
$dbname = "stormwind_library";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
