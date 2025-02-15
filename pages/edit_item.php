<?php
session_start(); // Ensure session is started
include '../includes/config.php';

$searchTerm = '';
if (isset($_GET['search'])) {
    $searchTerm = $_GET['search'];
}

if (isset($_GET['item_id'])) {
    $itemId = $_GET['item_id'];
    $query = "SELECT * FROM items WHERE item_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    echo json_encode($item);
    exit;
}

// Fetch items from the database based on search term
$query = "SELECT item_id, name FROM items WHERE name LIKE ?";
$stmt = $conn->prepare($query);
$searchTermWildcard = '%' . $searchTerm . '%';
$stmt->bind_param("s", $searchTermWildcard);
$stmt->execute();
$result = $stmt->get_result();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $itemId = $_POST['itemId'];
    $itemName = $_POST['itemName'];
    $itemDescription = $_POST['itemDescription'];
    $itemPrice = $_POST['itemPrice'];
    $itemType = $_POST['itemType'];
    $requiredLevel = $_POST['requiredLevel'];
    $rarity = $_POST['rarity'];
    $editAll = isset($_POST['editAll']);

    if ($editAll) {
        $updateQuery = "UPDATE items SET description = ?, price = ?, item_type = ?, required_level = ?, rarity = ? WHERE name = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("sissss", $itemDescription, $itemPrice, $itemType, $requiredLevel, $rarity, $itemName);
    } else {
        $updateQuery = "UPDATE items SET name = ?, description = ?, price = ?, item_type = ?, required_level = ?, rarity = ? WHERE item_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ssisssi", $itemName, $itemDescription, $itemPrice, $itemType, $requiredLevel, $rarity, $itemId);
    }

    if ($stmt->execute()) {
        echo "Item(s) updated successfully!";
    } else {
        echo "Error updating item(s): " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Item</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
    <div class="container mx-auto p-6 max-w-lg">
        <h2 class="text-2xl font-bold mb-4">Edit Item</h2>
        <form method="GET" class="mb-4 flex">
            <input type="text" id="search" name="search" placeholder="Search Item" value="<?php echo htmlspecialchars($searchTerm); ?>" class="flex-grow mt-1 p-2 bg-gray-700 border border-gray-600 rounded-md shadow-sm text-white">
            <button type="submit" class="ml-2 bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">Search</button>
        </form>

        <form method="POST" class="bg-gray-800 p-6 rounded-lg shadow-lg">
            <label for="itemId" class="block text-sm font-medium text-gray-300">Select Item</label>
            <select id="itemId" name="itemId" required class="mt-1 block w-full p-2 bg-gray-700 border border-gray-600 rounded-md shadow-sm mb-4 text-white">
                <option value="">--none--</option>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <option value="<?php echo $row['item_id']; ?>"><?php echo $row['name']; ?></option>
                <?php endwhile; ?>
            </select>

            <label for="itemName" class="block text-sm font-medium text-gray-300">Name</label>
            <input type="text" id="itemName" name="itemName" required class="mt-1 block w-full p-2 bg-gray-700 border border-gray-600 rounded-md shadow-sm mb-4 text-white">

            <label for="itemType" class="block text-sm font-medium text-gray-300">Type</label>
            <select id="itemType" name="itemType" required class="mt-1 block w-full p-2 bg-gray-700 border border-gray-600 rounded-md shadow-sm mb-4 text-white">
                <option value="Potion">Potion</option>
                <option value="Scroll">Scroll</option>
                <option value="Book">Book</option>
                <option value="Tome">Tome</option>
                <option value="Artifact">Artifact</option>
                <option value="Weapon">Weapon</option>
            </select>

            <label for="itemPrice" class="block text-sm font-medium text-gray-300">Price</label>
            <input type="number" id="itemPrice" name="itemPrice" required class="mt-1 block w-full p-2 bg-gray-700 border border-gray-600 rounded-md shadow-sm mb-4 text-white">

            <label for="requiredLevel" class="block text-sm font-medium text-gray-300">Required Level</label>
            <select id="requiredLevel" name="requiredLevel" required class="mt-1 block w-full p-2 bg-gray-700 border border-gray-600 rounded-md shadow-sm mb-4 text-white">
                <option value="none">None</option>
                <option value="apprentice">Apprentice</option>
                <option value="adept">Adept</option>
                <option value="mage">Mage</option>
                <option value="archmage">Archmage</option>
            </select>

            <label for="rarity" class="block text-sm font-medium text-gray-300">Rarity</label>
            <input type="text" id="rarity" name="rarity" class="mt-1 block w-full p-2 bg-gray-700 border border-gray-600 rounded-md shadow-sm mb-4 text-white">

            <label for="itemDescription" class="block text-sm font-medium text-gray-300">Description</label>
            <textarea id="itemDescription" name="itemDescription" required class="mt-1 block w-full p-2 bg-gray-700 border border-gray-600 rounded-md shadow-sm mb-4 text-white" rows="4"></textarea>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input type="checkbox" id="editAll" name="editAll" class="mr-2">
                    <label for="editAll" class="text-sm font-medium text-gray-300">Edit all similar items</label>
                </div>
                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded">Update Item</button>
            </div>
        </form>
    </div>

    <script>
        $(document).ready(function() {
            $('#itemId').change(function() {
                var itemId = $(this).val();
                if (itemId === "") {
                    $('#itemName').val('');
                    $('#itemDescription').val('');
                    $('#itemPrice').val('');
                    $('#itemType').val('');
                    $('#requiredLevel').val('');
                    $('#rarity').val('');
                } else {
                    $.ajax({
                        url: 'edit_item.php',
                        type: 'GET',
                        data: { item_id: itemId },
                        success: function(data) {
                            var item = JSON.parse(data);
                            $('#itemName').val(item.name);
                            $('#itemDescription').val(item.description);
                            $('#itemPrice').val(item.price);
                            $('#itemType').val(item.item_type);
                            $('#requiredLevel').val(item.required_level);
                            $('#rarity').val(item.rarity);
                        }
                    });
                }
            });
        });
    </script>
</body>
</html> 