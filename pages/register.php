<?php
include '../includes/config.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']); 
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $magic_class = $_POST['magic_class'];
    $level = 'Apprentice'; 
    $gold_coins = 100;


    $checkQuery = "SELECT * FROM players WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $message = "<span class='text-red-400 font-bold'>Username or email already exists!</span>";
    } else {
        $query = "INSERT INTO players (username, email, password, magic_class, level, gold_coins) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssi", $username, $email, $password, $magic_class, $level, $gold_coins);
        
        if ($stmt->execute()) {
            $message = "<span class='text-green-400 font-bold'>Registration successful! 
            <a href='login.php' class='text-blue-400 hover:underline'>Login here</a></span>";
        } else {
            $message = "<span class='text-red-400 font-bold'>Error: " . $stmt->error . "</span>";
        }
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Register | Stormwind Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white flex flex-col items-center h-screen">
    <div class="bg-gray-800 p-6 rounded-lg shadow-lg w-96" style="margin: auto;">
        <h2 class="text-xl font-bold mb-4 text-center">Register</h2>

        
        <?php if (!empty($message)): ?>
            <p class="text-center mb-3"><?php echo $message; ?></p>
        <?php endif; ?>

        <form method="POST">
            <label class="block mb-2">Username</label>
            <input type="text" name="username" class="w-full p-2 mb-3 bg-gray-700 border border-gray-600 rounded" required>
            
            <label class="block mb-2">Email</label>
            <input type="email" name="email" class="w-full p-2 mb-3 bg-gray-700 border border-gray-600 rounded" required>
            
            <label class="block mb-2">Password</label>
            <input type="password" name="password" class="w-full p-2 mb-3 bg-gray-700 border border-gray-600 rounded" required>
            
            <label class="block mb-2">Magic Class</label>
            <select name="magic_class" class="w-full p-2 mb-8 bg-gray-700 border border-gray-600 rounded" required>
                <option value="fire">Fire</option>
                <option value="water">Water</option>
                <option value="ice">Ice</option>
                <option value="nature">Nature</option>
                <option value="dark">Dark</option>
                <option value="light">Light</option>
            </select>
            
            <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 p-2 rounded">Register</button>
        </form>

        <div class="mt-4 text-center">
            <a href="login.php" class="text-blue-400 hover:underline">Already have an account? Login here.</a>
        </div>
    </div>
</body>
</html>
