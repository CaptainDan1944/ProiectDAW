<?php
session_start();
include '../includes/config.php';


$query = "SELECT ts.training_id, ts.title, ts.level, p.username AS trainer, p.magic_class AS trainer_class FROM training_sessions ts JOIN players p ON ts.trainer_id = p.player_id";
$result = $conn->query($query);

$isMageOrArchmage = isset($_SESSION['level']) && ($_SESSION['level'] == 'Mage' || $_SESSION['level'] == 'Archmage' || $_SESSION['is_admin'] == '1');

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <title>Trainings | Stormwind Library</title>
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
                <?php if ($isMageOrArchmage): ?>
                    <a href="create_training.php" class="bg-green-500 hover:bg-green-600 text-white font-bold py-1 px-3 rounded text-sm">Create Training</a>
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
    <div class="container mx-auto p-6">
        <h2 class="text-2xl font-bold mb-4">Available Trainings</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php
                    // Determine the prefix based on the level
                    $level = strtolower($row['level']);
                    $prefix = '';
                    switch ($level) {
                        case 'apprentice':
                            $prefix = 'Basic';
                            break;
                        case 'adept':
                            $prefix = 'Advanced';
                            break;
                        case 'mage':
                        case 'archmage':
                            $prefix = 'Expert';
                            break;
                    }

                    // Construct the image name
                    $class = ucfirst(strtolower($row['trainer_class']));
                    $imageName = "{$prefix}{$class}Magic.png";
                    $imagePath = "../resources/magic/{$imageName}";
                ?>
                <a href="training_details.php?id=<?php echo $row['training_id']; ?>" class="block bg-gray-800 p-4 pb-6 rounded-lg shadow-lg hover:bg-gray-700 transition duration-300 text-center">
                    <img src="<?php echo $imagePath; ?>" alt="<?php echo $row['title']; ?>" class="w-48 h-48 relative overflow-hidden mx-auto rounded-md mb-4">
                    <h3 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($row['title']); ?></h3>
                    <p>Trainer: <?php echo htmlspecialchars($row['trainer']); ?></p>
                    <p>Class: <?php echo htmlspecialchars(ucfirst($row['trainer_class'])); ?></p>
                    <p>Required Level: <?php echo htmlspecialchars(ucfirst($row['level'])); ?></p>
                </a>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html> 