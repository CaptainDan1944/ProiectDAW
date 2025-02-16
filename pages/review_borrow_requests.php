<?php
session_start();
include '../includes/config.php';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = $_POST['request_id'];
    $action = $_POST['action'];

    $query = "SELECT p.email, p.username, i.name AS item_name, bi.borrow_date, bi.return_date 
              FROM borrowed_items bi 
              JOIN players p ON bi.player_id = p.player_id 
              JOIN items i ON bi.item_id = i.item_id 
              WHERE bi.borrow_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $requestId);
    $stmt->execute();
    $result = $stmt->get_result();
    $emailData = $result->fetch_assoc();
    $stmt->close();

    if (!$emailData) {
        exit("Borrow request not found.");
    }

    $to = $emailData['email'];
    $username = $emailData['username'];
    $item_name = $emailData['item_name'];
    $borrow_date = date('Y-m-d', strtotime($emailData['borrow_date']));
    $return_date = date('Y-m-d', strtotime($emailData['return_date']));
    $subject = "";
    $message = "";

    if ($action === 'accept') {
        $updateQuery = "UPDATE borrowed_items SET status = 'accepted' WHERE borrow_id = ?";
        $subject = "Borrow Request Approved";
        $message = "Hello $username,\n\nYour request to borrow '$item_name' has been APPROVED.\n\nBorrow Date: $borrow_date\nReturn Date: $return_date\n\nThank you!";
    
    } elseif ($action === 'refuse') {
        $updateQuery = "UPDATE borrowed_items SET status = 'refused' WHERE borrow_id = ?";
        $subject = "Borrow Request Rejected";
        $message = "Hello $username,\n\nUnfortunately, your request to borrow '$item_name' has been REJECTED.\n\nYou may try again later.\nThank you for understanding.";

    } elseif ($action === 'borrow') {
        $updateQuery = "UPDATE borrowed_items SET status = 'borrowed' WHERE borrow_id = ?";
        $subject = "Item Borrowed";
        $message = "Hello $username,\n\nYou have successfully BORROWED '$item_name'.\n\nReturn Date: $return_date\nPlease return it on time.";

    } elseif ($action === 'cancel') {
        $updateQuery = "UPDATE borrowed_items SET status = 'cancelled' WHERE borrow_id = ?";
        $subject = "Borrow Request Cancelled";
        $message = "Hello $username,\n\nYour borrow request for '$item_name' has been CANCELLED.\n\nIf this was a mistake, please request again.";

    } elseif ($action === 'returned') {
        $updateQuery = "UPDATE borrowed_items SET status = 'returned', actual_return_date = NOW() WHERE borrow_id = ?";
        $subject = "Item Returned";
        $message = "Hello $username,\n\nThank you for returning '$item_name'.\n\nYour borrow request has been marked as RETURNED.";
    }

    if (isset($updateQuery)) {
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $stmt->close();
    }

    // Send email notification
    $headers = "From: no-reply@captaindan1944.com\r\n";
    $headers .= "Reply-To: no-reply@captaindan1944.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    mail($to, $subject, $message, $headers);

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