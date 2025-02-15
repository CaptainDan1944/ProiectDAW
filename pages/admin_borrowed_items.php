<?php
include '../includes/config.php';
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

// Handle search request
$search_results = [];
if (isset($_POST['search'])) {
    $player_name = $_POST['player_name'];
    $query = "SELECT i.name AS item_name, bi.return_date 
              FROM borrowed_items bi 
              JOIN items i ON bi.item_id = i.item_id 
              JOIN players p ON bi.player_id = p.player_id 
              WHERE p.username LIKE ? AND bi.status != 'returned' 
              ORDER BY bi.return_date ASC";
    $stmt = $conn->prepare($query);
    $search_param = "%" . $player_name . "%";
    $stmt->bind_param("s", $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    $search_results = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin | Borrowed Items</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white">
    <nav class="bg-gray-800 p-4">
        <div class="container mx-auto flex justify-between">
            <div class="flex space-x-4">
                <a href="admin.php" class="text-white hover:text-gray-400 border-r border-gray-700 pr-4">Admin</a>
                <a href="admin_borrowed_items.php" class="text-white hover:text-gray-400 border-r border-gray-700 pr-4">Borrowed Items</a>
            </div>

            <div class="flex items-center space-x-4">
                <span class="text-white"><?php echo $_SESSION['username']; ?></span>
                <a href="logout.php" class="text-red-500 hover:text-red-400">Logout</a>
            </div>
        </div>
    </nav>
    <div class="container mx-auto p-6">
        <h2 class="text-2xl font-bold mb-4">Search Borrowed Items</h2>
        <form method="POST" class="mb-4">
            <input type="text" name="player_name" placeholder="Enter player's name" class="text-black p-2 rounded" required>
            <button type="submit" name="search" class="bg-blue-500 text-white p-2 rounded">Search</button>
        </form>

        <?php if (!empty($search_results)): ?>
            <div class="bg-gray-800 p-4 rounded-lg shadow-lg">
                <h3 class="text-xl font-bold mb-4">Borrowed Items</h3>
                <ul>
                    <?php foreach ($search_results as $item): ?>
                        <li class="mb-2 font-bold">
                            <?php echo htmlspecialchars($item['item_name']); ?> - 
                            <span class="text-green-500">
                                Return by: <?php echo htmlspecialchars((new DateTime($item['return_date']))->format('F j')); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 