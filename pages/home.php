<?php
include '../includes/config.php';
session_start();
if (!isset($_SESSION['player_id'])) {
    header("Location: login.php");
    exit;
}

// Retrieve user data
$user_id = $_SESSION['player_id'];
$query = "SELECT username, level FROM players WHERE player_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Fetch borrowed items for the user, excluding those marked as 'returned', sorted by return_date
$borrowed_items = [];
$query = "SELECT i.name AS item_name, bi.return_date 
          FROM borrowed_items bi 
          JOIN items i ON bi.item_id = i.item_id 
          WHERE bi.player_id = ? AND bi.status != 'returned' 
          ORDER BY bi.return_date ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$borrowed_items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch promotion requests if user is an Archmage
$requests = [];
if ($user['level'] === 'Archmage') {
    $query = "SELECT p.promotion_id, pl.username, pl.level 
              FROM promotions p 
              JOIN players pl ON p.promoted_player = pl.player_id 
              WHERE p.promoted_by = ? AND p.reviewed = 0";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $requests = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Fetch upcoming trainings for the user
$upcoming_trainings = [];
$query = "SELECT t.title, t.start_time 
          FROM training_sessions t 
          WHERE t.start_time > NOW() 
          ORDER BY t.start_time ASC";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$upcoming_trainings = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Home | Stormwind Library</title>
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
    <div class="container mx-auto p-6">
        <h2 class="text-2xl font-bold mb-4">Welcome, <?php echo htmlspecialchars($user['username']); ?>!</h2>
        <p class="mt-4" style="margin-bottom: 4vh;">Here you can find all the items, trainings, and more that you need to become a powerful mage.</p>

        <?php if (!empty($borrowed_items)): ?>
            <div class="bg-gray-800 p-4 rounded-lg shadow-lg mb-6">
                <h3 class="text-xl font-bold mb-4">Borrowed Items</h3>
                <ul>
                    <?php foreach ($borrowed_items as $item): ?>
                        <?php
                        $return_date = new DateTime($item['return_date']);
                        $current_date = new DateTime();
                        $interval = $current_date->diff($return_date);
                        $days_left = $interval->days;
                        $is_late = $current_date > $return_date;
                        $date_color = '';

                        if ($is_late) {
                            $date_color = 'text-red-500'; // Late
                        } elseif ($days_left <= 3) {
                            $date_color = 'text-yellow-500'; // Less than 3 days
                        } else {
                            $date_color = 'text-green-500'; // More than 3 days
                        }
                        ?>
                        <li class="mb-2 font-bold">
                            <?php echo htmlspecialchars($item['item_name']); ?> - 
                            <span class="<?php echo $date_color; ?>">
                                Return by: <?php echo htmlspecialchars($return_date->format('F j')); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($user['level'] === 'Archmage' && !empty($requests)): ?>
            <div class="bg-gray-800 p-4 rounded-lg shadow-lg mb-6" style="max-width: 20vw;">
                <h3 class="text-xl font-bold mb-4">Promotion Requests</h3>
                <ul>
                    <?php foreach ($requests as $request): ?>
                        <a href="review_promotion.php?promotion_id=<?php echo $request['promotion_id']; ?>" class="block mb-2 p-2 border border-gray-600 rounded hover:bg-gray-700">
                            <li>
                                <?php echo htmlspecialchars($request['username']); ?> (<?php echo htmlspecialchars($request['level']); ?>)
                            </li>
                        </a>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($upcoming_trainings)): ?>
            <div class="bg-gray-800 p-4 rounded-lg shadow-lg mb-6">
                <h3 class="text-xl font-bold mb-4">Upcoming Trainings</h3>
                <ul>
                    <?php foreach ($upcoming_trainings as $training): ?>
                        <li class="mb-2 font-bold">
                            <?php echo htmlspecialchars($training['title']); ?> - 
                            <span class="text-green-500">
                                Starts at: <?php echo htmlspecialchars((new DateTime($training['start_time']))->format('F j, g:i A')); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="flex flex-col items-center mt-8">
            <h2 class="text-2xl font-bold mb-4">Contact The Librarian</h2>
            <form action="send_email.php" method="POST" class="bg-gray-800 p-4 rounded-lg shadow-lg mb-6 w-full max-w-md">
                <div class="mb-4">
                    <label for="name" class="block text-sm font-bold mb-2">Name:</label>
                    <input type="text" id="name" name="name" required class="w-full p-2 border border-gray-600 rounded">
                </div>
                <div class="mb-4">
                    <label for="email" class="block text-sm font-bold mb-2">Email:</label>
                    <input type="email" id="email" name="email" required class="w-full p-2 border border-gray-600 rounded">
                </div>
                <div class="mb-4">
                    <label for="message" class="block text-sm font-bold mb-2">Message:</label>
                    <textarea id="message" name="message" required class="w-full p-2 border border-gray-600 rounded" rows="4"></textarea>
                </div>
                <button type="submit" class="bg-blue-500 text-white p-2 rounded">Send Message</button>
            </form>
        </div>
    </div>
</body>
</html>
