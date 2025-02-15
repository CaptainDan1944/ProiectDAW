<?php
include 'includes/config.php'; // Ensure this file contains the database connection

session_start();

// Set a cookie for 30 days to track returning visitors
if (!isset($_COOKIE['returning_visitor'])) {
    setcookie('returning_visitor', 'yes', time() + (30 * 24 * 60 * 60)); // Expires in 30 days

    // Log the new visitor
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $query = "INSERT INTO site_visits (ip_address) VALUES (?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $ip_address);
    $stmt->execute();
}

// Get total unique visitors
$totalVisitorsQuery = "SELECT COUNT(DISTINCT ip_address) AS total_visitors FROM site_visits";
$result = $conn->query($totalVisitorsQuery);
$row = $result->fetch_assoc();
$total_visitors = $row['total_visitors'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Stormwind Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white flex justify-center items-center h-screen">
    <div class="bg-gray-800 p-6 rounded-lg shadow-lg text-center">
        <h1 class="text-3xl font-bold mb-4">Welcome to Stormwind Library</h1>
        <p class="mb-4">A mystical archive for wizards of all kinds.</p>
        
        <a href="pages/login.php" class="block bg-blue-500 hover:bg-blue-600 p-2 rounded my-2">Login</a>
        <a href="pages/register.php" class="block bg-green-500 hover:bg-green-600 p-2 rounded my-2">Register</a>
    </div>
</body>
</html>

