<?php
session_start();
include '../includes/config.php';


if (!isset($_SESSION['player_id'])) {
    echo "Session player_id is not set.";
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['player_id'];
$query = "SELECT username, email, level, magic_class, created_at FROM players WHERE player_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// promotion request
$pending_request = false;
$check_query = "SELECT COUNT(*) as count FROM promotions WHERE promoted_player = ? AND reviewed = 0";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
if ($check_result->fetch_assoc()['count'] > 0) {
    $pending_request = true;
}
$check_stmt->close();

// Fetch Archmages of the same class
$archmage_query = "SELECT player_id, username FROM players WHERE magic_class = ? AND level = 'archmage'";
$archmage_stmt = $conn->prepare($archmage_query);
$archmage_stmt->bind_param("s", $user['magic_class']);
$archmage_stmt->execute();
$archmage_result = $archmage_stmt->get_result();
$archmages = $archmage_result->fetch_all(MYSQLI_ASSOC);
$archmage_stmt->close();


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_promotion']) && !$pending_request) {
    if (empty($_POST['archmage_id'])) {
        echo "<p class='text-red-500'>Please select an Archmage to handle your request!</p>";
    } else {
        $player_id = $_SESSION['player_id'];
        $archmage_id = $_POST['archmage_id'];

        $stmt = $conn->prepare("INSERT INTO promotions (promoted_player, promoted_by, reviewed) VALUES (?, ?, 0)");
        $stmt->bind_param("ii", $player_id, $archmage_id);
        $stmt->execute();
        $stmt->close();

        echo "<p class='text-green-500'>Promotion request submitted!</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Profile | Stormwind Library</title>
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
                <span class="text-white"><?php echo htmlspecialchars($user['username']); ?></span>
                <a href="logout.php" class="text-red-500 hover:text-red-400">Logout</a>
            </div>
        </div>
    </nav>
    <div class="p-6 mx-auto" style="max-width: 86%;">
        <h2 class="text-2xl font-bold mb-4">Profile</h2>
        <div class="bg-gray-800 p-4 rounded-lg shadow-lg" style="width: 30%; margin-bottom: 4vh;">
            <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><strong>Level:</strong> <?php echo htmlspecialchars(ucfirst($user['level'])); ?></p>
            <p><strong>Class:</strong> <?php echo htmlspecialchars(ucfirst($user['magic_class'])); ?></p>
            <p><strong>Joined:</strong> <?php echo htmlspecialchars($user['created_at']); ?></p>
        </div>

        <?php if ($user['level'] !== 'archmage' && !$pending_request): ?>
            <form method="POST" class="mt-4">
                <label for="archmage" class="block text-sm font-medium text-gray-300 mb-2">Select an Archmage to evaluate your skill and decide if you are ready to be promoted:</label>
                <select name="archmage_id" id="archmage" class="bg-gray-700 text-white p-2 rounded mb-4" style="width: 20%;">
                    <option value="">-- Select an Archmage --</option>
                    <?php foreach ($archmages as $archmage): ?>
                        <option value="<?php echo $archmage['player_id']; ?>"><?php echo htmlspecialchars($archmage['username']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="request_promotion" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                    Request Promotion
                </button>
            </form>
        <?php elseif ($pending_request): ?>
            <p class="text-yellow-500 mt-4">You have already submitted a promotion request. Please wait for it to be reviewed.</p>
        <?php endif; ?>
    </div>
</body>
</html> 