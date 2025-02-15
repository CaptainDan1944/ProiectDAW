<?php
session_start();
include '../includes/config.php';

$itemId = $_GET['item_id'] ?? null;

if (!$itemId) {
    header("Location: items.php");
    exit;
}

// Fetch gold coins amount for the logged-in player
$goldCoins = 0;
if (isset($_SESSION['player_id'])) {
    $playerId = $_SESSION['player_id'];
    $goldQuery = "SELECT gold_coins FROM players WHERE player_id = ?";
    $stmt = $conn->prepare($goldQuery);
    $stmt->bind_param("i", $playerId);
    $stmt->execute();
    $stmt->bind_result($goldCoins);
    $stmt->fetch();
    $stmt->close();
}

// Fetch item details
$query = "SELECT * FROM items WHERE item_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $itemId);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Determine border class based on rarity
$borderClass = '';
switch (strtolower($item['rarity'])) {
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

// Define level hierarchy
$levelHierarchy = [
    'apprentice' => 1,
    'adept' => 2,
    'mage' => 3,
    'archmage' => 4
];

// Handle form submission for buying an item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy'])) {
    if ($item['stock'] <= 0) {
        echo "<script>alert('This item is out of stock.');</script>";
    } else {
        // Fetch player's level
        $levelQuery = "SELECT level FROM players WHERE player_id = ?";
        $stmt = $conn->prepare($levelQuery);
        $stmt->bind_param("i", $playerId);
        $stmt->execute();
        $stmt->bind_result($playerLevel);
        $stmt->fetch();
        $stmt->close();

        // Convert levels to lowercase for comparison
        $playerLevel = strtolower($playerLevel);
        $requiredLevel = strtolower($item['required_level']);

        // Check if player meets the level requirement
        if ($levelHierarchy[$playerLevel] < $levelHierarchy[$requiredLevel]) {
            echo "<script>alert('You do not meet the required level to buy this item.');</script>";
        } elseif ($goldCoins < $item['price']) {
            // Check if player has enough gold
            echo "<script>alert('You do not have enough gold to buy this item.');</script>";
        } else {
            // Deduct gold and add item to inventory
            $conn->begin_transaction();

            try {
                // Deduct gold
                $updateGoldQuery = "UPDATE players SET gold_coins = gold_coins - ? WHERE player_id = ?";
                $stmt = $conn->prepare($updateGoldQuery);
                $stmt->bind_param("ii", $item['price'], $playerId);
                $stmt->execute();
                $stmt->close();

                // Add item to inventory
                $addItemQuery = "INSERT INTO user_inventory (player_id, item_id, status) VALUES (?, ?, 'bought')";
                $stmt = $conn->prepare($addItemQuery);
                $stmt->bind_param("ii", $playerId, $itemId);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                echo "<script>alert('Item purchased successfully!');</script>";
            } catch (Exception $e) {
                $conn->rollback();
                echo "<script>alert('An error occurred while processing your purchase.');</script>";
            }
        }
    }
}

// Handle form submission for borrowing an item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrow'])) {
    // Fetch player's level
    $levelQuery = "SELECT level FROM players WHERE player_id = ?";
    $stmt = $conn->prepare($levelQuery);
    $stmt->bind_param("i", $playerId);
    $stmt->execute();
    $stmt->bind_result($playerLevel);
    $stmt->fetch();
    $stmt->close();

    // Convert levels to lowercase for comparison
    $playerLevel = strtolower($playerLevel);
    $requiredLevel = strtolower($item['required_level']);

    // Check if player meets the level requirement
    if ($levelHierarchy[$playerLevel] < $levelHierarchy[$requiredLevel]) {
        echo "<script>alert('You do not meet the required level to borrow this item.');</script>";
    } else {
        $borrowDate = $_POST['borrow_date'];
        $duration = $_POST['duration'];

        // Calculate return date
        $returnDate = date('Y-m-d', strtotime($borrowDate . " + $duration days"));

        // Check for existing pending requests
        $pendingQuery = "
            SELECT COUNT(*) FROM borrowed_items 
            WHERE player_id = ? AND item_id = ? AND status = 'pending'";
        $stmt = $conn->prepare($pendingQuery);
        $stmt->bind_param("ii", $playerId, $itemId);
        $stmt->execute();
        $stmt->bind_result($pendingCount);
        $stmt->fetch();
        $stmt->close();

        if ($pendingCount > 0) {
            echo "<script>alert('You already have a pending borrow request for this item.');</script>";
        } else {
            // Check for conflicting borrow periods
            $conflictQuery = "
                SELECT borrow_date, return_date FROM borrowed_items 
                WHERE item_id = ? AND status = 'borrowed' 
                AND (
                    (borrow_date <= ? AND return_date >= ?) OR
                    (borrow_date <= ? AND return_date >= ?) OR
                    (borrow_date >= ? AND return_date <= ?)
                )";
            $stmt = $conn->prepare($conflictQuery);
            $stmt->bind_param("issssss", $itemId, $borrowDate, $borrowDate, $returnDate, $returnDate, $borrowDate, $returnDate);
            $stmt->execute();
            $stmt->bind_result($conflictStart, $conflictEnd);
            $stmt->fetch();
            $stmt->close();

            if ($conflictStart) {
                echo "<script>alert('This item is already reserved for the following period: $conflictStart -> $conflictEnd. Please choose a different time period.');</script>";
            } else {
                // Insert borrow request into borrowed_items table
                $borrowRequestQuery = "INSERT INTO borrowed_items (player_id, item_id, borrow_date, return_date, status) VALUES (?, ?, ?, ?, 'pending')";
                $stmt = $conn->prepare($borrowRequestQuery);
                $stmt->bind_param("iiss", $playerId, $itemId, $borrowDate, $returnDate);
                $stmt->execute();
                $stmt->close();

                echo "<script>alert('Borrow request submitted for admin approval.');</script>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($item['name']); ?> | Stormwind Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .border-common { border-color: #a0a0a0; }
        .border-rare { border-color: #4a90e2; }
        .border-epic { border-color: #9b59b6; }
        .border-legendary { border-color: #e67e22; }
    </style>
</head>
<body class="bg-gray-900 text-white flex items-center justify-center min-h-screen">
    <nav class="bg-gray-800 p-4 fixed top-0 w-full">
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
    <div class="p-6 mx-auto mt-16 flex justify-center" style="max-width: 86%;">
        <div class="bg-gray-800 p-4 rounded-lg shadow-lg flex flex-col items-center" style="max-width: 24vw;">
            <h2 class="text-2xl font-bold mb-4 text-center"><?php echo htmlspecialchars($item['name']); ?></h2>
            <div class="w-48 h-48 relative overflow-hidden border-2 rounded-lg <?php echo $borderClass; ?> mx-auto mb-4">
                <img src="<?php echo $item['image_path']; ?>" alt="<?php echo $item['name']; ?>" class="w-full h-full object-cover">
            </div>
            <p class="mb-2 text-center">Type: <?php echo $item['item_type']; ?></p>
            <p class="mb-2 text-center">Price: <?php echo $item['price'] == 0 ? 'Not for Sale' : $item['price'] . ' gold'; ?></p>
            <p class="mb-2 text-center">Required Level: <?php echo ucfirst($item['required_level']); ?></p>
            <p class="mb-2 text-center"><?php echo $item['description']; ?></p>
            <form method="POST" class="mt-4">
                <?php if ($item['is_borrowable']): ?>
                    <div class="mb-4">
                        <label for="borrow_date" class="block text-white">Desired Borrow Date:</label>
                        <input type="date" id="borrow_date" name="borrow_date" class="bg-gray-700 text-white rounded p-2 w-full" required>
                    </div>
                    <div class="mb-4">
                        <label for="duration" class="block text-white">Duration (days):</label>
                        <input type="number" id="duration" name="duration" class="bg-gray-700 text-white rounded p-2 w-full" min="1" required>
                    </div>
                    <button type="submit" name="borrow" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">Submit Borrow Request</button>
                <?php else: ?>
                    <button type="button" class="bg-gray-500 text-white font-bold py-2 px-4 rounded" disabled>Not Available</button>
                <?php endif; ?>
            </form>
        </div>
    </div>
</body>
</html> 
