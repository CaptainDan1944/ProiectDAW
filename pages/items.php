<?php
session_start();
include '../includes/config.php';

// Fetch gold coins amount for the logged-in user
$goldCoins = 0;
if (isset($_SESSION['player_id'])) {
    $userId = $_SESSION['player_id'];
    $goldQuery = "SELECT gold_coins FROM players WHERE player_id = ?";
    $stmt = $conn->prepare($goldQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($goldCoins);
    $stmt->fetch();
    $stmt->close();
}

// Fetch all items from the database
$query = "
    SELECT 
        i.item_id, 
        i.name, 
        i.item_type, 
        i.price, 
        i.image_path, 
        i.required_level, 
        i.description, 
        i.rarity, 
        (COUNT(*) - 
            (SELECT COUNT(*) FROM user_inventory ui WHERE ui.item_id = i.item_id) - 
            (SELECT COUNT(*) FROM borrowed_items bi WHERE bi.item_id = i.item_id AND bi.status = 'borrowed')
        ) as stock 
    FROM items i 
    GROUP BY i.name
";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Items | Stormwind Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .border-common { border-color: #a0a0a0; }
        .border-rare { border-color: #4a90e2; }
        .border-epic { border-color: #9b59b6; }
        .border-legendary { border-color: #e67e22; }
    </style>
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
                <span class="text-yellow-500"><?php echo $goldCoins; ?> gold</span>
                <span class="text-white"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <?php endif; ?>
            <a href="logout.php" class="text-red-500 hover:text-red-400">Logout</a>
        </div>
    </div>
</nav>
    <div class="p-6 mx-auto" style="max-width: 86%;">
        <h2 class="text-2xl font-bold">Items</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-2">
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php
                    $borderClass = '';
                    switch (strtolower($row['rarity'])) {
                        case 'common':
                            $borderClass = 'border-common';
                            break;
                        case 'rare':
                            $borderClass = 'border-rare';
                            break;
                        case 'epic':
                            $borderClass = 'border-epic';
                            break;
                        case 'legendary':
                            $borderClass = 'border-legendary';
                            break;
                    }
                ?>
                <div class="bg-gray-800 p-4 rounded-lg shadow-lg flex flex-col items-center w-80 mx-auto h-full">
                    <a href="item_details.php?item_id=<?php echo $row['item_id']; ?>" class="w-full">
                        <div class="w-48 h-48 relative overflow-hidden border-2 rounded-lg <?php echo $borderClass; ?> mx-auto">
                            <img src="<?php echo $row['image_path']; ?>" alt="<?php echo $row['name']; ?>" class="w-full h-full object-cover">
                        </div>
                        <h3 class="text-xl font-bold mt-4 text-center"><?php echo $row['name']; ?></h3>
                    </a>
                    <p class="text-center">Type: <?php echo $row['item_type']; ?></p>
                    <p class="text-center">
                        Price: 
                        <?php echo $row['price'] == 0 ? 'Not for Sale' : $row['price'] . ' gold'; ?>
                    </p>
                    <p class="text-center">Required Level: <?php echo ucfirst($row['required_level']); ?></p>
                    <p class="text-center">Stock: <?php echo $row['stock']; ?></p>
                    <p class="mt-2 text-center"><?php echo $row['description']; ?></p>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html> 