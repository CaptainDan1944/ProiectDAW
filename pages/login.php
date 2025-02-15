<?php
include '../includes/config.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $query = "SELECT * FROM players WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['player_id'] = $row['player_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['magic_class'] = $row['magic_class'];
            $_SESSION['level'] = $row['level'];
            $_SESSION['gold_coins'] = $row['gold_coins'];
            $_SESSION['is_admin'] = $row['is_admin'];

            header("Location: home.php");
            exit;
        } else {
            echo "Invalid credentials!";
        }
    } else {
        echo "User not found!";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login | Stormwind Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white flex justify-center items-center h-screen">
    <div class="bg-gray-800 p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-bold mb-4">Login</h2>
        <form method="POST">
            <label class="block">Username</label>
            <input type="text" name="username" class="w-full p-2 mb-3 bg-gray-700 border border-gray-600 rounded" required>
            
            <label class="block">Password</label>
            <input type="password" name="password" class="w-full p-2 mb-3 bg-gray-700 border border-gray-600 rounded" required>
            
            <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 p-2 rounded">Login</button>
        </form>
        <div class="mt-4 text-center">
            <a href="register.php" class="text-blue-400 hover:underline">Don't have an account? Register here.</a>
        </div>
    </div>
</body>
</html>
