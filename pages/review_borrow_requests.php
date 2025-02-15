<?php
session_start();
include '../includes/config.php';

// Check if the user is an admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

// Fetch pending, accepted, and borrowed borrow requests
$query = "
    SELECT bi.borrow_id, bi.borrow_date, bi.return_date, p.username, i.name AS item_name, bi.status
    FROM borrowed_items bi
    JOIN players p ON bi.player_id = p.player_id
    JOIN items i ON bi.item_id = i.item_id
    WHERE bi.status IN ('pending', 'accepted', 'borrowed')";
$result = $conn->query($query);

// Handle form submission for borrowing, cancelling, and returning
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = $_POST['request_id'];
    $action = $_POST['action'];

    if ($action === 'accept') {
        $updateQuery = "UPDATE borrowed_items SET status = 'accepted' WHERE borrow_id = ?";
        // Prepare and execute the update query
        if (isset($updateQuery)) {
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("i", $requestId);
            $stmt->execute();
            $stmt->close();
        }

        //     // Fetch user email and item details for the email
        //     $emailQuery = "SELECT p.email, p.username, i.name AS item_name, bi.borrow_date, bi.return_date 
        //                 FROM borrowed_items bi 
        //                 JOIN players p ON bi.player_id = p.player_id 
        //                 JOIN items i ON bi.item_id = i.item_id 
        //                 WHERE bi.borrow_id = ?";
        //     $emailStmt = $conn->prepare($emailQuery);
        //     $emailStmt->bind_param("i", $requestId);
        //     $emailStmt->execute();
        //     $emailResult = $emailStmt->get_result();
        //     $emailData = $emailResult->fetch_assoc();

        //     // Send confirmation email
        //     $to = $emailData['email'];
        //     $subject = "Borrow Request Accepted";
        //     $message = "Hello, {$emailData['username']}!\n\nYour request to borrow '{$emailData['item_name']}' has been accepted.\nBorrow Date: {$emailData['borrow_date']}\nReturn Date: {$emailData['return_date']}\n\nThank you!";
        //     $headers = "From: headquarters@captaindan1944.com\r\n"; // Replace with your email

        //     // Send the email
        //     if (mail($to, $subject, $message, $headers)) {
        //         echo "Email sent successfully.";
        //     } else {
        //         echo "Email sending failed.";
        //     }
        // }
    } elseif ($action === 'refuse') {
        $updateQuery = "UPDATE borrowed_items SET status = 'refused' WHERE borrow_id = ?";
        // Prepare and execute the update query
        if (isset($updateQuery)) {
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("i", $requestId);
            $stmt->execute();
            $stmt->close();
        }
    } elseif ($action === 'borrow') {
        $updateQuery = "UPDATE borrowed_items SET status = 'borrowed' WHERE borrow_id = ?";
        // Prepare and execute the update query
        if (isset($updateQuery)) {
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("i", $requestId);
            $stmt->execute();
            $stmt->close();
        }
    } elseif ($action === 'cancel') {
        $updateQuery = "UPDATE borrowed_items SET status = 'cancelled' WHERE borrow_id = ?";
        // Prepare and execute the update query
        if (isset($updateQuery)) {
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("i", $requestId);
            $stmt->execute();
            $stmt->close();
        }
    } elseif ($action === 'returned') {
        $updateQuery = "UPDATE borrowed_items SET status = 'returned', actual_return_date = NOW() WHERE borrow_id = ?";
        // Prepare and execute the update query
        if (isset($updateQuery)) {
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("i", $requestId);
            $stmt->execute();
            $stmt->close();
        }
    }

    header("Location: review_borrow_requests.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Review Borrow Requests | Stormwind Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white">
    <nav class="bg-gray-800 p-4">
        <div class="container mx-auto flex justify-between">
            <div class="flex space-x-4">
                <a href="home.php" class="text-white hover:text-gray-400 border-r border-gray-700 pr-4">Home</a>
                <a href="admin.php" class="text-white hover:text-gray-400 border-r border-gray-700 pr-4">Admin</a>
                <a href="review_borrow_requests.php" class="text-white hover:text-gray-400 border-r border-gray-700 pr-4">Review Borrow Requests</a>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-white"><?php echo $_SESSION['username']; ?></span>
                <a href="logout.php" class="text-red-500 hover:text-red-400">Logout</a>
            </div>
        </div>
    </nav>
    <div class="p-6 mx-auto" style="max-width: 86%;">
        <h2 class="text-2xl font-bold mb-4">Pending, Accepted, and Borrowed Requests</h2>
        <table class="min-w-full bg-gray-800 text-white mt-4 rounded-lg shadow-lg">
            <thead>
                <tr class="bg-gray-700">
                    <th class="py-2 px-4 text-center">Player</th>
                    <th class="py-2 px-4 text-center">Item</th>
                    <th class="py-2 px-4 text-center">Borrow Date</th>
                    <th class="py-2 px-4 text-center">Return Date</th>
                    <th class="py-2 px-4 text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-600">
                        <td class="py-2 px-4 text-center"><?php echo htmlspecialchars($row['username']); ?></td>
                        <td class="py-2 px-4 text-center"><?php echo htmlspecialchars($row['item_name']); ?></td>
                        <td class="py-2 px-4 text-center"><?php echo date('Y-m-d', strtotime($row['borrow_date'])); ?></td>
                        <td class="py-2 px-4 text-center"><?php echo date('Y-m-d', strtotime($row['return_date'])); ?></td>
                        <td class="py-2 px-4 text-center">
                            <form method="POST" class="inline">
                                <input type="hidden" name="request_id" value="<?php echo $row['borrow_id']; ?>">
                                <?php if ($row['status'] === 'pending'): ?>
                                    <button type="submit" name="action" value="accept" class="bg-green-500 hover:bg-green-600 text-white font-bold py-1 px-3 rounded">Accept</button>
                                    <button type="submit" name="action" value="refuse" class="bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-3 rounded">Refuse</button>
                                <?php elseif ($row['status'] === 'accepted'): ?>
                                    <button type="submit" name="action" value="borrow" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-1 px-3 rounded">Borrow</button>
                                    <button type="submit" name="action" value="cancel" class="bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-3 rounded">Cancel</button>
                                <?php elseif ($row['status'] === 'borrowed'): ?>
                                    <button type="submit" name="action" value="returned" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-1 px-3 rounded">Returned</button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html> 