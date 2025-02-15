<?php
session_start();
include '../includes/config.php';

$trainingId = $_GET['id'] ?? null;
$userId = $_SESSION['player_id'] ?? null; // Assuming user ID is stored in session

if (!$trainingId || !$userId) {
    header("Location: trainings.php");
    exit;
}

// Fetch training details and user level
$query = "SELECT ts.title, ts.description, ts.start_time, ts.duration, ts.level, ts.trainer_id, p.username AS trainer, p.magic_class AS trainer_class, p.level AS trainer_level
          FROM training_sessions ts
          JOIN players p ON ts.trainer_id = p.player_id
          WHERE ts.training_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $trainingId);
$stmt->execute();
$result = $stmt->get_result();
$training = $result->fetch_assoc();

// Fetch user level
$query = "SELECT level FROM players WHERE player_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Check if user is already registered
$query = "SELECT * FROM training_participants WHERE training_id = ? AND player_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $trainingId, $userId);
$stmt->execute();
$isRegistered = $stmt->get_result()->num_rows > 0;

// Calculate end time
$startTime = new DateTime($training['start_time']);
$endTime = clone $startTime;
$endTime->modify("+{$training['duration']} minutes");
$date = $startTime->format('Y-m-d');
$interval = $startTime->format('H:i') . ' - ' . $endTime->format('H:i');

// Determine the image path based on class and level
$class = ucfirst(strtolower($training['trainer_class']));
$level = strtolower($training['level']);
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
$imageName = "{$prefix}{$class}Magic.png";
$imagePath = "../resources/magic/{$imageName}";

// Define levels with correct casing
$levels = [
    'apprentice' => 1,
    'adept' => 2,
    'mage' => 3,
    'archmage' => 4,
    'admin' => 5 // If needed
];

// Fetch user level
$userLevelValue = $levels[strtolower($user['level'])];
$trainingLevelValue = $levels[strtolower($training['level'])];

$isCreator = $training['trainer_id'] == $userId; // Check if the user is the creator

// Fetch participants if the user is the creator
$participants = [];
if ($isCreator) {
    $query = "SELECT pl.username FROM training_participants tp JOIN players pl ON tp.player_id = pl.player_id WHERE tp.training_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $trainingId);
    $stmt->execute();
    $result = $stmt->get_result();
    $participants = $result->fetch_all(MYSQLI_ASSOC);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($training['title']); ?> | Stormwind Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function attendTraining(userLevel, trainingLevel) {
            if (userLevel < trainingLevel) {
                document.getElementById('message').innerText = 'This training is too Advanced for you!';
                document.getElementById('message').classList.add('text-red-500');
            } else {
                fetch('register_training.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ training_id: <?php echo $trainingId; ?> })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const registerButton = document.getElementById('registerButton');
                        registerButton.innerText = 'Already Registered';
                        registerButton.disabled = true;
                        registerButton.classList.remove('bg-blue-500', 'hover:bg-blue-600');
                        registerButton.classList.add('bg-gray-500');
                        document.getElementById('message').innerText = 'Registered Successfully!';
                        document.getElementById('message').classList.add('text-green-500');
                    } else {
                        document.getElementById('message').innerText = 'Registration failed!';
                        document.getElementById('message').classList.add('text-red-500');
                    }
                });
            }
        }

        function deleteTraining(trainingId) {
            if (confirm('Are you sure you want to delete this training?')) {
                fetch('delete_training.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ training_id: trainingId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Training deleted successfully!');
                        window.location.href = 'trainings.php';
                    } else {
                        alert('Failed to delete training.');
                    }
                });
            }
        }
    </script>
</head>
<body class="bg-gray-900 text-white flex flex-col min-h-screen">
    <nav class="bg-gray-800 p-4">
        <div class="container mx-auto flex justify-between items-center">
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
                <span class="text-white"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php" class="text-red-500 hover:text-red-400">Logout</a>
            </div>
        </div>
    </nav>
    <div class="flex-grow flex items-center justify-center">
        <div class="bg-gray-800 p-6 rounded-lg shadow-lg text-center max-w-md">
            <img src="<?php echo $imagePath; ?>" alt="<?php echo $training['title']; ?>" class="w-48 h-48 mx-auto rounded-md mb-4">
            <h2 class="text-2xl font-bold mb-4"><?php echo htmlspecialchars($training['title']); ?></h2>
            <p class="mb-4"><?php echo htmlspecialchars($training['description']); ?></p>
            <p>Trainer: <?php echo htmlspecialchars(ucfirst($training['trainer_level']) . ' ' . $training['trainer']); ?></p>
            <p>Date & Time: <?php echo htmlspecialchars($date . ' | ' . $interval); ?></p>
            <p>Level: <?php echo htmlspecialchars(ucfirst($training['level'])); ?></p>
            <?php if (!$isCreator): ?>
                <?php if ($isRegistered): ?>
                    <button class="mt-4 bg-gray-500 text-white font-bold py-2 px-4 rounded" disabled>
                        Already Registered
                    </button>
                <?php else: ?>
                    <button id="registerButton" class="mt-4 bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded"
                            onclick="attendTraining(<?php echo $userLevelValue; ?>, <?php echo $trainingLevelValue; ?>)">
                        Attend Training
                    </button>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($isCreator): ?>
                <button class="mt-4 bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded"
                        onclick="deleteTraining(<?php echo $trainingId; ?>)">
                    Cancel Training
                </button>
                <div class="mt-6">
                    <h3 class="text-xl font-bold mb-2">Participants</h3>
                    <ul class="list-disc list-inside">
                        <?php foreach ($participants as $participant): ?>
                            <li><?php echo htmlspecialchars($participant['username']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <p id="message" class="mt-4"></p>
        </div>
    </div>
</body>
</html> 