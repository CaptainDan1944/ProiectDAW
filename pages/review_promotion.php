<?php
session_start();
include '../includes/config.php';

// Check if user is an Archmage
if ($_SESSION['level'] !== 'Archmage') {
    header("Location: home.php");
    exit;
}

// Get promotion request details
$promotion_id = $_GET['promotion_id'];
$query = "SELECT pl.player_id, pl.username, pl.email, pl.level, pl.magic_class, pl.created_at, p.new_level FROM promotions p JOIN players pl ON p.promoted_player = pl.player_id WHERE p.promotion_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $promotion_id);
$stmt->execute();
$result = $stmt->get_result();
$player = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reviewed = $_POST['action'] === 'approve' ? 1 : -1;
    $comment = $_POST['comment'];

    // Update promotion status and comment
    $stmt = $conn->prepare("UPDATE promotions SET reviewed = ?, comment = ? WHERE promotion_id = ?");
    $stmt->bind_param("isi", $reviewed, $comment, $promotion_id);
    $stmt->execute();
    $stmt->close();

    // If approved, update player's level
    if ($reviewed === 1) {
        $new_level = $player['new_level'];
        $player_id = $player['player_id'];
        $stmt = $conn->prepare("UPDATE players SET level = ? WHERE player_id = ?");
        $stmt->bind_param("si", $new_level, $player_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: home.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Review Promotion</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white flex flex-col items-center justify-center min-h-screen">
    <nav class="bg-gray-800 p-4 w-full fixed top-0">
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
            <a href="logout.php" class="text-red-500 hover:text-red-400">Logout</a>
        </div>
    </nav>
    <div class="bg-gray-800 p-6 rounded-lg shadow-lg mt-20" style="max-width: 600px; width: 100%;">
        <h2 class="text-2xl font-bold mb-4 text-center">Review Promotion for <?php echo htmlspecialchars($player['username']); ?></h2>
        <div class="bg-gray-700 p-4 rounded-lg shadow mb-4">
            <p><strong>Username:</strong> <?php echo htmlspecialchars($player['username']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($player['email']); ?></p>
            <p><strong>Level:</strong> <?php echo htmlspecialchars($player['level']); ?></p>
            <p><strong>Class:</strong> <?php echo htmlspecialchars(ucfirst($player['magic_class'])); ?></p>
            <p><strong>Joined:</strong> <?php echo htmlspecialchars($player['created_at']); ?></p>
        </div>
        <form method="POST">
            <textarea name="comment" class="bg-gray-700 text-white p-2 rounded w-full mb-4" placeholder="Add a comment..."></textarea>
            <div class="flex justify-between">
                <button type="submit" name="action" value="approve" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded">Approve</button>
                <button type="submit" name="action" value="disapprove" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded">Disapprove</button>
            </div>
        </form>
    </div>
</body>
</html> 