<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

include '../includes/config.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $item_type = $_POST['item_type'];
    $price = intval($_POST['price']);
    $required_level = $_POST['required_level'];
    $description = $_POST['description'];
    $rarity = $_POST['rarity'];


    $target_dir = "../uploads/";
    $target_file = $target_dir . basename($_FILES["image"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if image file is a actual image or fake image
    $check = getimagesize($_FILES["image"]["tmp_name"]);
    if ($check === false) {
        $uploadOk = 0; 
    }

    // Check if file already exists
    if (file_exists($target_file)) {
        $uploadOk = 1;
    }

    if ($_FILES["image"]["size"] > 5000000) {
        $uploadOk = 0; 
    }

    // Allow certain file formats
    if (!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) {
        $uploadOk = 0; 
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
    } else {
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_path = $target_file;
            
            $is_borrowable = isset($_POST['is_borrowable']) ? 1 : 0;

            
            $query = "INSERT INTO items (name, item_type, price, image_path, required_level, description, rarity, is_borrowable) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssdssssi", $name, $item_type, $price, $image_path, $required_level, $description, $rarity, $is_borrowable);

            if ($stmt->execute()) {
                $message = "Item created successfully!";
            } else {
                $message = "Error: " . $stmt->error; 
            }
            $stmt->close();
        } else {
            $message = "Error: File upload failed."; 
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Create Item | Stormwind Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white">
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
                <?php if (isset($_SESSION['username'])): ?>
                    <span class="text-white"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <?php endif; ?>
                <a href="logout.php" class="text-red-500 hover:text-red-400">Logout</a>
            </div>
        </div>
    </nav>
    <div class="container mx-auto p-6" style="max-width: 25%; margin-top: 20px;">
        <h2 class="text-2xl font-bold mb-4">Create New Item</h2>
        <form method="POST" enctype="multipart/form-data" class="bg-gray-800 p-6 rounded-lg shadow-lg">
            <label class="block mb-2">Name</label>
            <input type="text" name="name" class="w-full p-2 mb-3 bg-gray-700 border border-gray-600 rounded" required>
            
            <label class="block mb-2">Type</label>
            <select name="item_type" class="w-full p-2 mb-3 bg-gray-700 border border-gray-600 rounded" required>
                <option value="Potion">Potion</option>
                <option value="Scroll">Scroll</option>
                <option value="Book">Book</option>
                <option value="Tome">Tome</option>
                <option value="Artifact">Artifact</option>
                <option value="Weapon">Weapon</option>
            </select>
            
            <label class="block mb-2">Price</label>
            <input type="number" step="1" name="price" class="w-full p-2 mb-3 bg-gray-700 border border-gray-600 rounded" required>
            
            <label class="block mb-2">Image</label>
            <input type="file" name="image" class="w-full p-2 mb-3 bg-gray-700 border border-gray-600 rounded" required>
            
            <label class="block mb-2">Required Level</label>
            <select name="required_level" class="w-full p-2 mb-3 bg-gray-700 border border-gray-600 rounded" required>
                <option value="none">None</option>
                <option value="Apprentice">Apprentice</option>
                <option value="Adept">Adept</option>
                <option value="Mage">Mage</option>
                <option value="Archmage">Archmage</option>
            </select>

            <label class="block mb-2">Rarity</label>
            <select name="rarity" class="w-full p-2 mb-3 bg-gray-700 border border-gray-600 rounded" required>
                <option value="Common">Common</option>
                <option value="Uncommon">Uncommon</option>
                <option value="Rare">Rare</option>
                <option value="Epic">Epic</option>
                <option value="Legendary">Legendary</option>
            </select>
            
            <label class="block mb-2">Description</label>
            <textarea name="description" class="w-full p-3 mb-3 bg-gray-700 border border-gray-600 rounded" required></textarea>
            
            <label class="block mb-2">
                <input type="checkbox" name="is_borrowable" class="mr-2"> Is Borrowable
            </label>
            
            <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 p-2 rounded">Create Item</button>
        </form>
    </div>


    <?php if (!empty($message)): ?>
        <div class="container mx-auto p-4">
            <div class="bg-gray-800 p-4 rounded-lg shadow-lg text-center">
                <p class="font-bold"><?php echo htmlspecialchars($message); ?></p>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>
