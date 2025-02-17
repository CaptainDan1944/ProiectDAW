<?php
include '../includes/config.php';
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

$borrowed_items = [];

$query = "SELECT bi.borrow_id, i.name AS item_name, p.username, bi.borrow_date, bi.return_date, 
                 bi.actual_return_date, bi.status
          FROM borrowed_items bi
          JOIN items i ON bi.item_id = i.item_id
          JOIN players p ON bi.player_id = p.player_id
          ORDER BY bi.borrow_date DESC";

$result = $conn->query($query);
if ($result) {
    $borrowed_items = $result->fetch_all(MYSQLI_ASSOC);
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
        <h2 class="text-2xl font-bold mb-4">Borrow Requests</h2>

        <div class="bg-gray-800 p-4 rounded-lg shadow-lg">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-700">
                        <th class="p-2">Player</th>
                        <th class="p-2">Item</th>
                        <th class="p-2">Borrowed On</th>
                        <th class="p-2">Return By</th>
                        <th class="p-2">Actual Return</th>
                        <th class="p-2">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($borrowed_items as $item): ?>
                        <tr class="border-b border-gray-600">
                            <td class="p-2"><?php echo htmlspecialchars($item['username']); ?></td>
                            <td class="p-2"><?php echo htmlspecialchars($item['item_name']); ?></td>
                            <td class="p-2"><?php echo htmlspecialchars((new DateTime($item['borrow_date']))->format('F j, Y')); ?></td>
                            <td class="p-2"><?php echo htmlspecialchars($item['return_date'] ? (new DateTime($item['return_date']))->format('F j, Y') : 'N/A'); ?></td>
                            <td class="p-2"><?php echo $item['actual_return_date'] ? (new DateTime($item['actual_return_date']))->format('F j, Y') : '-'; ?></td>
                            <td class="p-2 font-bold">
                                <?php if ($item['status'] == 'accepted' && !$item['actual_return_date']): ?>
                                    <span class="text-yellow-500">Waiting for Pickup</span>
                                <?php elseif ($item['status'] == 'borrowed' && !$item['actual_return_date']): ?>
                                    <span class="text-green-500">Currently Borrowed</span>
                                <?php elseif ($item['actual_return_date']): ?>
                                    <span class="text-blue-500">Returned</span>
                                <?php else: ?>
                                    <span class="text-gray-400"><?php echo htmlspecialchars($item['status']); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($borrowed_items)): ?>
                        <tr><td colspan="6" class="text-center p-4">No borrow requests found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
