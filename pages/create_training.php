<?php
session_start();
if (!isset($_SESSION['level']) || 
    (!in_array($_SESSION['level'], ['Mage', 'Archmage']) && $_SESSION['is_admin'] != '1')) {
    header("Location: trainings.php");
    exit;
}

include '../includes/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $start_time = $_POST['start_time'];
    $duration = $_POST['duration'];
    $level = $_POST['level'];
    $trainer_id = $_SESSION['player_id'];

    $query = "INSERT INTO training_sessions (title, description, start_time, duration, level, trainer_id) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssisi", $title, $description, $start_time, $duration, $level, $trainer_id);

    if ($stmt->execute()) {
        echo "Training session created successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Create Training | Stormwind Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white">
<nav class="bg-gray-800 p-4">
    <div class="container mx-auto flex justify-between">
        <div class="flex space-x-4">
            <a href="home.php" class="text-white hover:text-gray-400 border-r border-gray-700 pr-4">Home</a>
            <a href="items.php" class="text-white hover:text-gray-400 border-r border-gray-700 pr-4">Items</a>
            <a href="profile.php" class="text-white hover:text-gray-400 border-r border-gray-700 pr-4">Profile</a>  
            <a href="trainings.php" class="text-white hover:text-gray-400 border-r border-gray-700 pr-4">Trainings</a>
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
                <a href="admin.php" class="text-white hover:text-gray-400 border-r border-gray-700 pr-4">Admin</a>
            <?php endif; ?>
        </div>
        <div class="flex items-center space-x-4">
            <?php if (isset($_SESSION['username'])): ?>
                <span class="text-white"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <?php endif; ?>
            <a href="logout.php" class="text-red-500 hover:text-red-400">Logout</a> 
        </div>
    </div>
</nav>
    <div class="container mx-auto p-6 max-w-lg">
        <h2 class="text-2xl font-bold mb-4">Create Training Session</h2>
        <form method="POST" class="bg-gray-800 p-6 rounded-lg shadow-lg">
            <label class="block text-sm font-medium text-gray-300">Title</label>
            <input type="text" name="title" class="w-full p-2 mb-3 bg-gray-700 border border-gray-600 rounded" required>

            <label class="block text-sm font-medium text-gray-300">Description</label>
            <textarea name="description" class="w-full p-2 mb-3 bg-gray-700 border border-gray-600 rounded" required></textarea>

            <label class="block text-sm font-medium text-gray-300">Start Time</label>
            <input type="datetime-local" name="start_time" class="w-full p-2 mb-3 bg-gray-700 border border-gray-600 rounded" required>

            <label class="block text-sm font-medium text-gray-300">Duration (minutes)</label>
            <input type="number" name="duration" class="w-full p-2 mb-3 bg-gray-700 border border-gray-600 rounded" required>

            <label class="block text-sm font-medium text-gray-300">Level</label>
            <select name="level" class="w-full p-2 mb-3 bg-gray-700 border border-gray-600 rounded" required>
                <option value="apprentice">Apprentice</option>
                <option value="adept">Adept</option>
                <option value="mage">Mage</option>
                <option value="archmage">Archmage</option>
            </select>

            <button type="submit" class="w-full bg-green-500 hover:bg-green-600 p-2 rounded">Create Training</button>
        </form>
    </div>
</body>
</html> 