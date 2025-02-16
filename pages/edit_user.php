<?php
session_start();
include '../includes/config.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

$userId = $_GET['user_id'] ?? null;
$user = [];
$users = [];

if ($userId) {
    $userQuery = "SELECT username, email, level, magic_class, gold_coins, is_admin FROM players WHERE player_id = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        echo "<script>alert('User not found!'); window.location.href='admin.php';</script>";
        exit;
    }
}

// User search
if (isset($_POST['search_user'])) {
    $searchUsername = $_POST['search_username'];
    $searchQuery = "SELECT player_id, username FROM players WHERE username LIKE ?";
    $stmt = $conn->prepare($searchQuery);
    $likeSearch = "%" . $searchUsername . "%";
    $stmt->bind_param("s", $likeSearch);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// User update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $newUsername = $_POST['username'];
    $newEmail = $_POST['email'];
    $newLevel = $_POST['level'];
    $newMagicClass = $_POST['magic_class'];
    $newGoldCoins = $_POST['gold_coins'];
    $isAdmin = isset($_POST['is_admin']) ? 1 : 0;

    $updateQuery = "UPDATE players SET username = ?, email = ?, level = ?, magic_class = ?, gold_coins = ?, is_admin = ? WHERE player_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("ssssiii", $newUsername, $newEmail, $newLevel, $newMagicClass, $newGoldCoins, $isAdmin, $userId);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('User details updated successfully!'); window.location.href='admin.php';</script>";
}

// User deletion
if (isset($_POST['delete_user'])) {
    $deleteQuery = "DELETE FROM players WHERE player_id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('User deleted successfully!'); window.location.href='admin.php';</script>";
}

$username = htmlspecialchars($user['username'] ?? '');
$email = htmlspecialchars($user['email'] ?? '');
$level = htmlspecialchars($user['level'] ?? '');
$magicClass = htmlspecialchars($user['magic_class'] ?? '');
$goldCoins = htmlspecialchars($user['gold_coins'] ?? '');


echo "<script>console.log('User Level: " . $level . "');</script>";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit User | Stormwind Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .input-field {
            max-width: 400px;
            width: 100%;
        }
    </style>
</head>
<body class="bg-gray-900 text-white">
    <nav class="bg-gray-800 p-4">
        <div class="container mx-auto flex justify-between">
            <div class="flex space-x-4">
                <a href="home.php" class="text-white hover:text-gray-400 border-r border-gray-700 pr-4">Home</a>
                <a href="admin.php" class="text-white hover:text-gray-400 border-r border-gray-700 pr-4">Admin</a>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-white"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php" class="text-red-500 hover:text-red-400">Logout</a>
            </div>
        </div>
    </nav>
    <div class="p-6 mx-auto" style="max-width: 600px;">
        <h2 class="text-2xl font-bold mb-4">Search User</h2>
        <form method="POST" class="flex">
            <input type="text" name="search_username" placeholder="Search by username" class="bg-gray-700 text-white rounded-l p-2 input-field" required>
            <button type="submit" name="search_user" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-r">Search</button>
        </form>
        <?php if (isset($users) && count($users) > 0): ?>
            <h3 class="text-xl font-bold mt-4">Search Results:</h3>
            <ul class="bg-gray-800 rounded p-4">
                <?php foreach ($users as $searchUser): ?>
                    <li>
                        <a href="?user_id=<?php echo $searchUser['player_id']; ?>" class="text-blue-400 hover:underline"><?php echo htmlspecialchars($searchUser['username']); ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        
        <h2 class="text-2xl font-bold mb-4 mt-6">Edit User Details</h2>
        <form method="POST">
            <div class="mb-4">
                <label for="username" class="block text-white">Username:</label>
                <input type="text" id="username" name="username" value="<?php echo $username; ?>" class="bg-gray-700 text-white rounded p-2 input-field" required>
            </div>
            <div class="mb-4">
                <label for="email" class="block text-white">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo $email; ?>" class="bg-gray-700 text-white rounded p-2 input-field" required>
            </div>
            <div class="mb-4">
                <label for="level" class="block text-white">Level:</label>
                <select id="level" name="level" class="bg-gray-700 text-white rounded p-2 input-field" required>
                    <option value="apprentice" <?php echo ($level === 'Apprentice') ? 'selected' : ''; ?>>Apprentice</option>
                    <option value="adept" <?php echo ($level === 'Adept') ? 'selected' : ''; ?>>Adept</option>
                    <option value="mage" <?php echo ($level === 'Mage') ? 'selected' : ''; ?>>Mage</option>
                    <option value="archmage" <?php echo ($level === 'Archmage') ? 'selected' : ''; ?>>Archmage</option>
                </select>
            </div>
            <div class="mb-4">
                <label for="magic_class" class="block text-white">Magic Class:</label>
                <select id="magic_class" name="magic_class" class="bg-gray-700 text-white rounded p-2 input-field" required>
                    <option value="fire" <?php echo ($magicClass === 'fire') ? 'selected' : ''; ?>>Fire</option>
                    <option value="water" <?php echo ($magicClass === 'water') ? 'selected' : ''; ?>>Water</option>
                    <option value="nature" <?php echo ($magicClass === 'nature') ? 'selected' : ''; ?>>Nature</option>
                    <option value="air" <?php echo ($magicClass === 'ice') ? 'selected' : ''; ?>>Ice</option>
                    <option value="light" <?php echo ($magicClass === 'light') ? 'selected' : ''; ?>>Light</option>
                    <option value="dark" <?php echo ($magicClass === 'dark') ? 'selected' : ''; ?>>Dark</option>
                </select>
            </div>
            <div class="mb-4">
                <label for="gold_coins" class="block text-white">Gold Coins:</label>
                <input type="number" id="gold_coins" name="gold_coins" value="<?php echo $goldCoins; ?>" class="bg-gray-700 text-white rounded p-2 input-field" required>
            </div>
            <div class="mb-4">
                <label for="is_admin" class="block text-white">Set as Admin:</label>
                <input type="checkbox" id="is_admin" name="is_admin" <?php echo ($user['is_admin']) ? 'checked' : ''; ?>>
            </div>
            <button type="submit" name="update_user" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">Update User</button>
            <button type="submit" name="delete_user" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded">Delete User</button>
        </form>
    </div>
</body>
</html> 