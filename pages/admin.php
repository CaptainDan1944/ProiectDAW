<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Panel | Stormwind Library</title>
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
                <span class="text-white"><?php echo $_SESSION['username']; ?></span>
                <a href="logout.php" class="text-red-500 hover:text-red-400">Logout</a>
            </div>
        </div>
    </nav>
    <div class="p-6 mx-auto" style="max-width: 86%;">
        <h2 class="text-2xl font-bold">Admin Panel</h2>
        <p>Welcome, Master Librarian <?php echo $_SESSION['username']; ?>. Your magic administration tools are at your disposal.</p>
        
        <!-- Button to create a new item -->
        <a href="create_item.php" class="inline-block bg-green-500 hover:bg-green-600 text-white font-bold py-1 px-3 rounded mt-2 text-sm">Create New Item</a>
        <a href="edit_item.php" class="inline-block bg-blue-500 hover:bg-blue-600 text-white font-bold py-1 px-3 rounded mt-2 text-sm">Edit Item</a>
        
        <!-- New button to review borrow requests -->
        <a href="review_borrow_requests.php" class="inline-block bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-1 px-3 rounded mt-2 text-sm">Review Borrow Requests</a>
        
        <a href="edit_user.php<?php echo isset($_SESSION['user_id']) ? '?user_id=' . $_SESSION['user_id'] : ''; ?>" 
        class="inline-block bg-purple-500 hover:bg-purple-600 text-white font-bold py-1 px-3 rounded mt-2 text-sm">Edit User</a>

        <!-- New button to view borrowed items -->
        <a href="admin_borrowed_items.php" class="inline-block bg-orange-500 hover:bg-orange-600 text-white font-bold py-1 px-3 rounded mt-2 text-sm">View Borrowed Items</a>
        
        <!-- New button to view user statistics -->
        <a href="admin_user_statistics.php" class="inline-block bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-3 rounded mt-2 text-sm">View User Statistics</a>
        
        <!-- Add more admin functionalities here -->
    </div>
</body>
</html>