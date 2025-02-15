<?php
include '../includes/config.php'; // Ensure this file contains the database connection

session_start();

if (!isset($user_statistics)) {
    $user_statistics = [];
}

// Fetch total unique visitors
$totalVisitorsQuery = "SELECT COUNT(DISTINCT ip_address) AS total_visitors FROM site_visits";
$result = $conn->query($totalVisitorsQuery);
$row = $result->fetch_assoc();
$total_visitors = $row['total_visitors'];

// Fetch user statistics from the database
$userStatisticsQuery = "SELECT username, COUNT(borrow_id) AS borrow_count FROM borrowed_items 
                        JOIN players ON borrowed_items.player_id = players.player_id 
                        GROUP BY username";
$result = $conn->query($userStatisticsQuery);
$user_statistics = $result->fetch_all(MYSQLI_ASSOC);

// Fetch user statistics by magic class
$magicClassQuery = "SELECT magic_class, COUNT(*) AS class_count FROM players GROUP BY magic_class";
$magicClassResult = $conn->query($magicClassQuery);
$magic_classes = [];
while ($row = $magicClassResult->fetch_assoc()) {
    $magic_classes[$row['magic_class']] = $row['class_count'];
}

// Prepare data for the pie chart
$labels = json_encode(array_keys($magic_classes));
$data = json_encode(array_values($magic_classes));
?>
<!DOCTYPE html>         
<html lang="en">
<head>
    <title>User Statistics | Stormwind Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-900 text-white">
<nav class="bg-gray-800 p-4">
    <div class="container mx-auto flex justify-between">
        <div class="flex space-x-4">
            <a href="home.php" class="text-white hover:text-gray-400 border-r border-gray-700 pr-4">Home</a>
            <a href="items.php" class="text-white hover:text-gray-400 border-r border-gray-700 pr-4">Items</a>
            <a href="profile.php" class="text-white hover:text-gray-400 border-r border-gray-700 pr-4">Profile</a>
            <a href="trainings.php" class="text-white hover:text-gray-400 border-r border-gray-700 pr-4">Trainings</a>
            <a href="admin.php" class="text-white hover:text-gray-400 border-r border-gray-700 pr-4">Admin</a>
        </div>
        <div class="flex items-center space-x-4">
            <span class="text-white"><?php echo $_SESSION['username']; ?></span>
            <a href="logout.php" class="text-red-500 hover:text-red-400">Logout</a>
        </div>
    </div>
</nav>
<div class="p-6 mx-auto" style="max-width: 86%;">
    <h2 class="text-2xl font-bold mb-4">User Statistics</h2>
    <p class="mb-4">Total Unique Visitors: <?php echo $total_visitors; ?></p>
    <p class="mb-4">Below are the statistics for each user based on their borrowing activity.</p>
    
    <table class="min-w-full bg-gray-800 rounded-lg shadow-lg align-center">
        <thead>
            <tr class="bg-gray-700">
                <th class="py-2 px-4 border-b border-gray-600 text-center">Username</th>
                <th class="py-2 px-4 border-b border-gray-600 text-center">Borrow Count</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($user_statistics)): ?>
                <?php foreach ($user_statistics as $user): ?>
                    <tr class="hover:bg-gray-600">
                        <td class="py-2 px-4 border-b border-gray-600 text-center"><?php echo htmlspecialchars($user['username']); ?></td>
                        <td class="py-2 px-4 border-b border-gray-600 text-center"><?php echo htmlspecialchars($user['borrow_count']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="2" class="py-2 px-4 border-b border-gray-600 text-center">No user statistics available.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <h3 class="text-xl font-bold mt-6">Generate Reports</h3>
    <p class="mb-2">Select a report format:</p>
    <a href="generate_report.php?type=csv" class="inline-block bg-green-500 hover:bg-green-600 text-white font-bold py-1 px-3 rounded mt-2 text-sm">Download CSV</a>
    <a href="generate_report.php?type=pdf" class="inline-block bg-blue-500 hover:bg-blue-600 text-white font-bold py-1 px-3 rounded mt-2 text-sm">Download PDF</a>
    <a href="generate_report.php?type=excel" class="inline-block bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-1 px-3 rounded mt-2 text-sm">Download Excel</a>

    <h3 class="text-xl font-bold mt-6">Magic Class Distribution</h3>
    <canvas id="magicClassChart" width="300" height="300"></canvas>

    <script>
        const ctx = document.getElementById('magicClassChart').getContext('2d');
        const magicClassChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: <?php echo $labels; ?>,
                datasets: [{
                    data: <?php echo $data; ?>,
                    backgroundColor: [
                        'rgb(235, 58, 27)',    // Fire - Red
                        'rgb(63, 63, 228)',    // Water - Blue
                        'rgb(86, 196, 216)',  // Ice - Light Blue/Turquoise
                        'rgb(44, 160, 29)',    // Nature - Green
                        'rgb(100, 15, 100)',  // Dark - Dark Purple
                        'rgba(211, 211, 211, 1)'  // Light - Light Pearly Gray
                    ],
                    borderColor: [
                        'rgba(255, 0, 0, 1)',      // Fire - Red
                        'rgba(0, 0, 255, 1)',      // Water - Blue
                        'rgba(0, 255, 255, 1)',    // Ice - Light Blue/Turquoise
                        'rgba(0, 255, 0, 1)',      // Nature - Green
                        'rgba(128, 0, 128, 1)',    // Dark - Dark Purple
                        'rgba(211, 211, 211, 1)'    // Light - Light Pearly Gray
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Magic Class Distribution'
                    }
                }
            }
        });
    </script>
</div>
</body>
</html> 